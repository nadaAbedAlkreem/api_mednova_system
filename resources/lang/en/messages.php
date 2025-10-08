<?php

return [
    'image' => [
        'required' => 'The image field is required.',
        'file' => 'The image must be a file.',
        'mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
        'max' => 'The image may not be greater than 2048 kilobytes.',
    ],
    'name_game' => [
        'required' => 'The game name field is required.',
        'max' => 'The name must be less than 255 characters'
    ],
    'type_game' => [
        'required' =>'The type game field is required.' ,
        'in' => 'The nature of the challenge must be determined, whether individual enter (I) or enter (T) team.',
    ],
    'unique' => 'unique ',

    'name' => [
        'required' => 'Name field is required.',
        'string' => 'Name field must be text.',
        'max' => 'Users cannot exceed 4.',

    ],
    'operation accomplished successfully' => 'Operation accomplished successfully',
    'INVALID_LEVEL'=> 'invalid level',
    'NO_IDS_PROVIDED' => 'NO IDS PROVIDED' ,
    'RESTORE_SUCCESS' => 'RESTORE_SUCCESS',
    'UPDATE_STATUS_USER_ACTIVE' => 'Update status user active',
    'EXPIRED_TIME'=>'Waiting time is up, try again.' ,
    'ERROR_FCM_TOKEN'=> 'User does not have a device token' ,
    'UPDATE_FCM_TOKEN_SUCCESSFULLY'=> 'Device token updated successfully' ,
    'PROFILE_UPDATED_SUCCESSFULLY' => 'Profile updated successfully' ,
    'FINANCIA_GET_SUCCESSFULLY' => 'Financial data acquisition process completed',
    'REGISTERED_SUCCESSFULLY' => 'Account created successfully',
    'BLOCKED_DEVICE' => 'Account is blocked',
    'REGISTRATION_CLOSED' => 'Registration closed by system admin',
    'AUTH_CODE_ERROR' => 'Activation code error, try again',
    'AUTH_CODE_SENT_BEFORE' => 'Auth code sent before, please check ful messages in inbox!',
    'NO_AUTH_CODE' => 'Process denied, incorrect',
    'UNAUTHORISED' => 'Unauthorised, please login',
    'IN_ACTIVE_ACCOUNT' => 'Please, activate your account, check ful messages in inbox',
    'ERROR_CREDENTIALS' => 'Error login credentials, check and try again',
    'LOGGED_OUT_SUCCESSFULLY' => 'Logged out successfully',
    'LOGGED_IN_SUCCESSFULLY' => 'Logged in successfully',
    'NO_DATA' => 'no data ' ,
    'LOGIN_IN_FAILED' => 'Login failed, try again!',
    'NO_ACCOUNT' => 'User mobile is not registered!',
    'AUTH_CODE_SENT' => 'Auth code sent successfully',
    'ACCOUNT_EXIST' => 'Email registered before!',
    'MOBILE_EXIST' => 'Mobile registered before!',
    'SUCCESS_AUTH' => 'Account activated successfully',
    'exam_fail' => 'We apologize, you could not pass the exam',
    'attempts' => 'You exceeded the the number of allowed attempts',
    'profile_exam' => 'please complate your profile',
    'ITEM_NOT_FOUND' => 'We can not found this record',
    'SEND_FAILED' => 'You have been alrdey send',
    'Not_IS_EMPTY' => 'The value cannot be null . ',
    'CREATE_USER_SUCCESSFULLY' => 'Create user successfully' ,
    'PASSWORD_RESET_SUCCESFULL'=>'Password reset successfully.' ,
    'TOO_MANY_ATTEMPTS'=>'Too many attempts' ,




    //PASSWORD
    'FORGET_PASSWORD_SUCCESS' => 'Password reset code sent successfully',
    'FORGET_PASSWORD_FAILED' => 'Failed to sent password reset code!',
    'PASSWORD_RESET_CODE_CORRECT' => 'Correct password reset code, set new password',
    'PASSWORD_RESET_CODE_ERROR' => 'Password reset code error, try again',
    'NO_PASSWORD_RESET_CODE' => 'No forget password request exist, process denied!',
    'PASS_RESET_CODE_SENT_BEFORE' => 'Password reset code sent before, please check messages in inbox!',
    'exp_date' => 'This Quiz Expired',

    'RESET_PASSWORD_SUCCESS' => 'Reset password success',
    'RESET_PASSWORD_FAILED' => 'Failed to reset password!',
    'CART_SUCCESS' => 'Add To Cart successfully',
    'PAGE_TRANSLATED_SUCCESS' => 'Page data has been successfully translated',
    'PAGE_TRANSLATED_FAILED' => 'Page data has been failed translated',

    'CONTACT_US_REQUEST_SUCCESS' => 'Contact request sent successfully, thanks',
    'CONTACT_US_REQUEST_FAILED' => 'Failed to send contact request, try again',

    'USER_UPDATED_SUCCESS' => 'Profile updated successfully',
    'USER_UPDATED_FAILED' => 'Profile update failed!, try again',

    'PASSWORD_SENT' => 'Password sent successfully, use it to login to your account',
    'PASSWORD_SEND_FAILED' => 'Failed to sent password, please try again',
    'PASSWORD_ALREADY_SET' => 'Password has been set before!',
    "PASSWORD_NOT_SET" => 'Please request your account password!',

    'MULTI_ACCESS_ERROR' => 'It is not possible to log in to the same account from two devices at once!',
    'SECURITY_CHECK_SUCCESS' => 'Your status has been sent successfully, keep safe',
    'SECURITY_CHECK_DUPLICATE' => 'Your status has been sent before, keep safe',
    'SECURITY_CHECK_FAILED' => 'There was a malfunction in submitting your case, please try again',
    "INVALID_TOKEN"=>'Invalid token or user.' ,
    "VALID_TOKEN"=>' valid token or user.' ,

    'CREATE_SUCCESS' => 'Created successfully',
    'CREATE_FAILED' => 'Create failed, please try again',
    'you_not_approved' => 'You are not approved',

    'DELETE_SUCCESS' => 'Deleted successfully',
    'DELETE_FAILED' => 'Delete failed, please try again',
    'REQUEST_SUCCESS' => 'Successfully',
    'UPDATE_SUCCESS' => 'Updated successfully',
    'favorite' => 'Added to your favorite list',
    'favorite_delete' => 'Removed from your favorite list',
    'PASSWORD_changed' => 'PASSWORD_changed',
    'NO_ACCESS_PERMISSION' => 'You dont have access permission to this component',
    'NOT_FOUND' => 'Object not fount',
    'REORDER_SUCCESS' => 'The request was successfully re-order',
    'INTERNAL_SERVER_ERROR'  => 'internal server error'  ,
    // ORDERS
    'ORDER_STATUS_UPDATED' => 'Order request updated',
    'ORDER_CREATE_SUCCESS' => 'Order successfully created',
    'UPDATE_FAILED' => 'Failed to update update',
    'INVALID_UPDATE_ELEMENT' => 'Invalid update item',
    'ORDER_NOT_FOUND' =>  'order not found',
    'ORDER_DETAILS_RETRIEVED' => 'order details retrieved' ,
    'ORDERS_RETRIEVED' => 'orders retrieved' ,
    'ORDERS_NOT_FOUND' => 'orders  not found' ,
    "PASSWORD_RESET_LINK_SEND"=>'Password reset link sent.' ,
    // Order Status
    'PENDING' => 'Your request was created successfully',
    'ACCEPTED' => 'Your request has been accepted by a distributor: ',
    'DECLINED' => 'Sorry, your order was rejected by a distributor:',
    'ONWAY' => 'The dealer is on your way',
    'PROCESSING' => 'Your order is being packed',
    'FILLED' => 'Your request has been filled',
    'DELIVERED' => 'Has your order been delivered, please confirm delivery',
    'COMPLETED' => 'We are here to serve you',
    'CANCELLED_BY_VENDOR' => 'We apologize your order has been canceled by a distributor :',
    'CANCELLED_BY_CUSTOMER' => 'The request was rejected by the customer:',
    'INVALIDE_SERVICE_TYPE'  => 'invalid service type' ,
    'CREATE_USERS_ACCOUNT' => 'successfuly create accout',
    'ID_NOT_FOUND' => 'The specified identifier was not found' ,
    'ITEMS_UPDATED_SUCCESSFULLY' => 'The items have been updated successfully.',
    'INVALID_DELIVERED_QUANTITY' => 'Delivered quantity cannot be greater than total quantity.',
    'EXCEPTION_MESSAGE' => 'An error occurred: :message',
    'ERROR_OCCURRED' => 'ERROR OCCURRED' ,
    'USER_NOT_FOUND' => 'User not found ' ,
    'TOKEN_VALID' => 'Token valid' ,
    'NOTAUTHORIZED'=> 'You are not authorized. You must log in first.' ,
    'TOKEN_EXPIRED'=> 'The code has expired. Try again later.'  ,
    'PURCHASES_ORDER_ITEMS_NOT_FOUND' =>  ' Purchase data cannot be found for this order.' ,
    'PURCHASES_ORDER_ITEMS_RETRIEVED' => 'Order purchases have been returned' ,
    'PAYMENTS_ORDER_RETRIEVED' => 'Order Payments have been returned' ,
    'PAYMENTS_ORDER_NOT_FOUND' => 'Payments data cannot be found for this order.'  ,
    'ATTACHMENTS_ORDER_DETAILS_RETRIEVED'  => 'Order attachments have been returned . '  ,
    'ATTACHMENTS_ORDER_NOT_FOUND' => 'Attachments data cannot be found for this order.' ,
    'VETIFICATION_ERRORS'  => 'There is an error in the entered data',
    'INVOICES_ORDER_DETAILS_RETRIEVED'  => 'Order invoices have been returned . '  ,
    'INVOICES_ORDER_NOT_FOUND' => 'invoices data cannot be found for this order.' ,
    'UPDATES_ORDER_DETAILS_RETRIEVED'  => 'Order updates have been returned . '  ,
    'UPDATES_ORDER_NOT_FOUND' => 'Updates data cannot be found for this order.' ,
    'CREATE_ITEM_SUCCESSFULLY' => 'Created successfully' ,
    'DELETE_ITEM_SUCCESSFULLY' => 'Deleted successfully' ,
    'DATA_RETRIEVED_SUCCESSFULLY' => 'Delivered data successfully',
    'DATA_RETRIEVED_FAILED' => 'Delivered data failed',
    'UNAUTHENTICATED'=> 'Unauthenticated' ,
    'TRAFFICKER_SUCCESSFULLY' => 'The tracking process has been completed. ',
    'NO_GAMES' => 'No games found for this user' ,
    'CREATE_ITEM_FAILD' => 'Created Failed' ,
    'DELETE_ITEM_FAILD' => 'Deleted Failed' ,
    'UPDATE_ELEMENT_NOT_FOUND' => 'Update failed You must enter the data you want to update',
    'CURL_ERROR' => 'Error curl' ,
    'NOTIFICATION_SENT_SUCCESSFULLY'=> 'Notification has been sent'  ,
    'delete_friend_request' => 'Friend request deleted',
    'accept_friend_request' => 'Friend request accepted',
    'CREATE_FRIEND_REQUEST_SUCCESSFULLY' => 'Friend request sent',
    'GAME_END' =>'The game has been completed.' ,
    'STORE_SCORE_SUCCESSFULLY' => 'store score success',
    'USER_DELETE_SUCCESS' => 'User account has been successfully deleted',
    'format_code' => 'The value must be in the format of + followed by one or more digits (e.g., "+2", "+970").' ,

    'members' => [
        'required' => 'Name field is required.',
        'string' => 'Phone number must be text.',
        'max' => 'Users cannot exceed 4.',
        'min' => 'Users cannot exceed 2.',

    ],
         'exists' => 'The location-pharmacy you attached do not exist.',


    'categories_id' => [
        'required' => 'The game type field is required',
        'integer' => 'The game type reference must be a number',
        'not_in' => 'The attached game type is invalid',
        'exists' => 'The game type does not exist' ,
        'array' => 'The reference game type must be a list',
        'min' => 'You must select at least 8 location-pharmacy',
        ],
    'blocked_Admin' => 'Your account has been blocked by the administrator',
    "INVALID_TOKEN"=>'Invalid token or user.' ,
    'TOKEN_EXPIRED'=> 'The code has expired. Try again later.'  ,
    'TOKEN_VALID' => 'Token valid' ,

    'TOO_MANY_ATTEMPTS'=>'Too many attempts' ,
    'Successfully updated changes.'=>'Successfully updated changes.' ,

    'user1_id' => [
        'required' => 'The value of the game origin does not exist',
        'integer' => 'The reference of the origin field must be a number',
        'exists' => 'The value returned from the origin field does not exist'
    ],
    'user2_id' => [
        'required' => 'The competitor field is required',
        'integer' => 'The competitor field reference must be a number',
        'exists' => 'The value returned from the competitor field does not exist'
    ],

    'challenge_id' => [
        'required' => 'The challenge ID is required.',
        'integer' => 'The challenge ID must be an integer.',
        'exists' => 'The selected challenge ID is invalid.',
    ],

    'first_competitor_id' => [
        'required' => 'The first competitor ID is required.',
        'integer' => 'The first competitor ID must be an integer.',
        'exists' => 'The selected first competitor ID is invalid.',
    ],

    'second_competitor_id' => [
        'required' => 'The second competitor ID is required.',
        'integer' => 'The second competitor ID must be an integer.',
        'exists' => 'The selected second competitor ID is invalid.',
    ],

    'winner_id' => [
        'required' => 'The winner ID is required.',
        'integer' => 'The winner ID must be an integer.',
        'exists' => 'The selected winner ID is invalid.',
    ],

    'score_FC' => [
        'required' => 'The score for competitor first is required.',
        'integer' => 'The score for competitor first must be an integer.',
        'max' => 'The score for competitor first must not exceed :max.',
    ],

    'score_SC' => [
        'required' => 'The score for competitor second is required.',
        'integer' => 'The score for competitor second must be an integer.',
        'max' => 'The score for competitor second must not exceed :max.',
    ],

    'name.required' => 'The name field is required.',
    'name.string' => 'The name must be a string.',
    'name.max' => 'The name may not be greater than 255 characters.',
    'competition_over_competitor_notification_title' => 'The competition is almost over!' ,
    'competition_over_competitor_notification_body' => 'Your friend has finished the game and only a little time is left.' ,
    'invitation_accept_competitor_notification_title'=> 'The challenge has begun !' ,
    'invitation_accept_competitor_notification_body' => 'Accept to join the Lets Go Challenge!',
    'invitation_notification_title' => 'New competition!' ,
    'invitation_notification_body' => 'Ahmed invited you to a competition in the Jawabna Lets go!' ,
    'accept_friend_request_notification_title' => 'Friend request accepted!' ,
    'accept_friend_request_notification_body' => 'accepted your friend request. Say hello and start a game together!' ,
    'full_name.required' => 'The full name field is required.',
    'full_name.string' => 'The full name must be a string.',
    'full_name.max' => 'The full name may not be greater than 255 characters.',
    'id.exists' => 'The specified class is not a valid base class.',
    'id.required' => 'The id field is required.',
    'id.integer' => 'The id must be a number.',
    'sender_id.required' => 'The name field is required.',
    'sender_id.exists' => 'The specified class is not a valid base class.',
    'receiver_id.required' => 'The name field is required.',
    'receiver_id.exists' => 'The specified class is not a valid base class.',
    'invalid_credentials' => 'Invalid password or email' ,
    'ERROR_OCCURRED' => 'ERROR OCCURRED' ,
    'id_not_found' => 'An error occurred: Item not found',
    'friend_request_exists' => 'You have already sent a friend request!',
    'friend_request_notification_title' => 'Request friendship' ,
    'friend_request_notification_body' => 'send you a friend request' ,
    'title.required' => 'The name field is required.',
    'title.string' => 'Name must be text.',
    'title.max' => 'The length of the name cannot exceed 255 characters.',

    'description.required' => 'The name field is required.',
    'description.string' => 'Name must be text.',
    'email.required' => 'The email field is required.',
    'email.string' => 'The email must be a string.',
    'email.email' => 'The email must be a valid email address.',
    'email.max' => 'The email may not be greater than 255 characters.',
    'email.unique' => 'The email has already been taken.',

    'phone.required' => 'The phone number field is required.',
    'phone.string' => 'The phone number must be a string.',
    'phone.unique' => 'The phone number has already been taken.',
    'phone.prefix' => 'The mobile number must contain the country prefix and consist of 9 to 14 digits.',
    'password' => [
        'required' => 'The password field is required.',
        'string' => 'The password must be a string.',
        'min' => 'The password must be at least :min characters.',
        'confirmed' => 'The password confirmation does not match.',
        'letters' => 'The password must contain at least one letter.',
        'mixed_case' => 'The password must contain both uppercase and lowercase letters.',
        'numbers' => 'The password must contain at least one number.',
        'symbols' => 'The password must contain at least one symbol.',
        'uncompromised' => 'The given password has appeared in a data breach. Please choose a different password.',
    ],

    'email' => [
        'required' => 'Email is required.',
        'email' => 'Email must be a valid email address.',
    ],

    'token' => [
        'required' => 'Token is required.',
        'digits' => 'Token must be a 4 digits',
    ],
    'country' => [
        'required' => 'Country is required.',

    ] ,
    'phone' => [
        'required' => 'Phone number is required.',
        'mobile_number' => 'Phone number is need prefix'
    ],
    'verification_method' => [
        'required' => 'Verification method is required.',
        'in' => 'The verification method must be either email or phone.',
    ],
    'name_ar' => [
        'required' => 'The game name in Arabic is required.',
        'string' => 'The game name in Arabic must be text.',
        'unique' => 'The game name in Arabic already exists.',
        'max' => 'The game name in Arabic must not exceed 255 characters.',
    ],

    'name_en' => [
        'required' => 'The game name in Arabic is required.',
        'string' => 'The game name in Arabic must be text.',
        'unique' => 'The game name in Arabic already exists.',
        'max' => 'The game name in Arabic must not exceed 255 characters.',
    ],

    'description_ar' => [
        'required' => 'The description in Arabic is required.',
        'string' => 'The description in Arabic must be text.',
        'max' => 'The description in Arabic must not exceed 255 characters.',
    ],
    'description_en' => [
        'required' => 'The description in Arabic is required.',
        'string' => 'The description in Arabic must be text.',
        'max' => 'The description in Arabic must not exceed 255 characters.',
    ],

    'rating' => [
        'required' => 'Rating is required.',
        'numeric' => 'Rating must be a number.',
        'min' => 'Rating must be greater than or equal to 0.',
        'max' => 'Rating must be less than or equal to 5.',
    ],



    'parent_id' => [
        'integer' => 'Parent ID must be an integer.',
        'min' => 'Parent ID must be greater than or equal to 0.',
        'exists' => 'Parent ID does not exist.',
    ],

    'famous_gaming' => [
        'boolean' => 'Property must be a boolean (true/false).',
    ],


];
