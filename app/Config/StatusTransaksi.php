<?php

namespace Config;

/**
 * Konstanta ID untuk tb_transaksi.id_status_transaksi
 */
class StatusTransaksi
{
    public const MENUNGGU = 1;
    public const PROSES   = 2;
    public const SETRIKA  = 3;
    public const SELESAI  = 4;
    public const DIAMBIL  = 5;

    /** Status yang masih dalam proses (belum selesai/diambil) */
    public const DIPROSES = [self::MENUNGGU, self::PROSES, self::SETRIKA];

    /** Status yang belum diambil (termasuk selesai) */
    public const BELUM_DIAMBIL = [self::MENUNGGU, self::PROSES, self::SETRIKA, self::SELESAI];

    /** Semua status */
    public const SEMUA = [self::MENUNGGU, self::PROSES, self::SETRIKA, self::SELESAI, self::DIAMBIL];
}
