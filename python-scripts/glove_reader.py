import serial
import requests
import struct
import time
import binascii   #   أضف هذا لاستعمال crc_hqx

# ----------------------------------------------------
# دالة للتحقق من CRC16
def check_crc16(frame):
    # بايت 0 إلى 17 (قبل CRC)
    data = frame[0:18]
    # بايت 18-19 = CRC المستلم
    received_crc = struct.unpack('<H', frame[18:20])[0]

    # حساب CRC16 Modbus
    calculated_crc = binascii.crc_hqx(data, 0xFFFF)

    return calculated_crc == received_crc

# ----------------------------------------------------
LARAVEL_API = "http://127.0.0.1:8000/api/glove-data"
PORT = "COM3"   # غيّرها حسب المنفذ عندك
BAUD = 115200

ser = serial.Serial(PORT, BAUD, timeout=1)

# سجل الأخطاء لمراقبة التكرار
error_log = {
    "last_error_flag": None,
    "repeat_count": 0
}

def read_frame():
    frame = ser.read(20)

    #   تحقق 1: من الهيكل العام
    if len(frame) != 20:
        print("❌ Frame length invalid")
        return None
    if frame[0] != 0xAA or frame[-1] != 0x55:
        print("❌ Frame header/footer invalid")
        return None
     #   تحقق CRC16
    if not check_crc16(frame):
        print("❌ CRC16 mismatch: data corrupted")
        return None
    #   استخراج البيانات الأساسية
    device_id = frame[1]
    status = frame[2]
    flex = struct.unpack('<5H', frame[3:12])      # 5 أصابع × 2 بايت
    heartbeat = struct.unpack('<H', frame[13:14])[0]
    temperature = struct.unpack('<H', frame[15:16])[0] / 100
    error_flag = frame[17]

    # ✅ تحقق 2: منطقية القيم
    if not (0 <= temperature <= 50):
        print(f"⚠️ Temperature out of range: {temperature}")
        return None
    if not (30 <= heartbeat <= 180):
        print(f"⚠️ Heartbeat out of range: {heartbeat}")
        return None

    return {
        "device_id": device_id,
        "status": status,
        "flex": flex,
        "heartbeat": heartbeat,
        "temperature": temperature,
        "error_flag": error_flag
    }

def send_to_laravel(data):
    try:
        r = requests.post(LARAVEL_API, json=data)
        print("✅ Sent:", data, "| Response:", r.status_code)
    except Exception as e:
        print("❌ Error sending to Laravel:", e)

def handle_error(device_id, error_flag):
    """يتحقق من تكرار الخطأ ويرسله للـ Laravel عند التكرار"""
    global error_log

    # خطأ جديد
    if error_flag != error_log["last_error_flag"]:
        error_log["last_error_flag"] = error_flag
        error_log["repeat_count"] = 1
        print(f"⚠️ New sensor error detected: {error_flag}")
    else:
        # تكرار نفس الخطأ
        error_log["repeat_count"] += 1
        print(f"⚠️ Repeated error {error_flag} ({error_log['repeat_count']}x)")

        # عند التكرار أكثر من 3 مرات، نرسل تنبيه للـ Laravel
        if error_log["repeat_count"] >= 3:
            send_to_laravel({
                "device_id": device_id,
                "error_flag": error_flag,
                "status": "sensor_error",
                "repeat_count": error_log["repeat_count"]
            })
            error_log["repeat_count"] = 0  # إعادة العد بعد الإرسال

# ----------------------------------------

while True:
    data = read_frame()

    if data:
        # ✅ تحقق 3: هل يوجد خطأ في المستشعرات؟
        if data["error_flag"] != 0x00:
            handle_error(data["device_id"], data["error_flag"])
        else:
            send_to_laravel(data)

    time.sleep(0.2)  # كل 200ms
