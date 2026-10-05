<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class Numbers
{
    // Must be called inside the enclosing business transaction.
    public static function next(int $clinic, string $key): int
    {
        DB::table('number_sequences')->insertOrIgnore(['clinic_id' => $clinic, 'key' => $key, 'value' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $row = DB::table('number_sequences')->where('clinic_id', $clinic)->where('key', $key)->lockForUpdate()->first();
        DB::table('number_sequences')->where('id', $row->id)->update(['value' => $row->value + 1, 'updated_at' => now()]);

        return $row->value + 1;
    }

    public static function document(int $clinic, string $prefix): string
    {
        return $prefix.'-'.$clinic.'-'.now()->format('Ymd').'-'.str_pad((string) self::next($clinic, $prefix.':'.today()->toDateString()), 5, '0', STR_PAD_LEFT);
    }
}
