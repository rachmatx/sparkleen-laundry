<?php

/**
 * Mendapatkan jumlah transaksi yang masih berstatus menunggu atau proses
 */
if (!function_exists('get_pending_trx_count')) {
    function get_pending_trx_count()
    {
        $db = \Config\Database::connect();
        return $db->table('tb_transaksi')
            ->whereIn('id_status_transaksi', [\Config\StatusTransaksi::MENUNGGU, \Config\StatusTransaksi::PROSES])
            ->countAllResults();
    }
}
