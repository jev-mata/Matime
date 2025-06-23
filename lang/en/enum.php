<?php

declare(strict_types=1);

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\Weekday;

return [

    'weekday' => [
        Weekday::Monday->value => 'Monday',
        Weekday::Tuesday->value => 'Tuesday',
        Weekday::Wednesday->value => 'Wednesday',
        Weekday::Thursday->value => 'Thursday',
        Weekday::Friday->value => 'Friday',
        Weekday::Saturday->value => 'Saturday',
        Weekday::Sunday->value => 'Sunday',
    ],

    'number_format' => [
        NumberFormat::ThousandsPointDecimalComma->value => '1.111,11',
        NumberFormat::ThousandsCommaDecimalPoint->value => '1,111.11',
        NumberFormat::ThousandsSpaceDecimalComma->value => '1 111,11',
        NumberFormat::ThousandsSpaceDecimalPoint->value => '1 111.11',
        NumberFormat::ThousandsApostropheDecimalPoint->value => '1\'111.11',
    ],

    'date_format' => [
        DateFormat::PointSeparatedDMYYYY->value => 'D.M.YYYY',
        DateFormat::SlashSeparatedMMDDYYYY->value => 'MM/DD/YYYY',
        DateFormat::SlashSeparatedDDMMYYYY->value => 'DD/MM/YYYY',
        DateFormat::HyphenSeparatedDDMMYYY->value => 'DD-MM-YYYY',
        DateFormat::HyphenSeparatedMMDDDYYYY->value => 'MM-DD-YYYY',
        DateFormat::HyphenSeparatedYYYYMMDD->value => 'YYYY-MM-DD',
    ],

    'time_format' => [
        TimeFormat::TwelveHours->value => '12-hour clock',
        TimeFormat::TwentyFourHours->value => '24-hour clock',
    ],

    'interval_format' => [
        IntervalFormat::Decimal->value => 'Decimal',
        IntervalFormat::HoursMinutes->value => '12h 3m',
        IntervalFormat::HoursMinutesColonSeparated->value => '12:03',
        IntervalFormat::HoursMinutesSecondsColonSeparated->value => '12:03:45',
    ],

    'currency_format' => [
        CurrencyFormat::ISOCodeBeforeWithSpace->value => 'EUR 111',
        CurrencyFormat::ISOCodeAfterWithSpace->value => '111 EUR',
        CurrencyFormat::SymbolBefore->value => '€111',
        CurrencyFormat::SymbolAfter->value => '111€',
        CurrencyFormat::SymbolBeforeWithSpace->value => '€ 111',
        CurrencyFormat::SymbolAfterWithSpace->value => '111 €',
    ],

];
