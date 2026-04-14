<?php

namespace App\Enums;

enum EntryType : String
{
    case ENTRY_DEBIT  = 'debit';
    case ENTRY_CREDIT = 'credit';
}
