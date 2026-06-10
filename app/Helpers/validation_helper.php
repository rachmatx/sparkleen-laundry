<?php

if (! function_exists('cekTypoEmail')) {
    /**
     * Memeriksa apakah domain email merupakan typo dari domain populer.
     * Mengembalikan false jika terdeteksi typo, true jika aman.
     */
    function cekTypoEmail($email)
    {
        if (empty($email)) return true;
        
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        
        $typoDomains = [
            'gmaill.com', 'gmail.con', 'gamil.com', 'gmai.com', 'gmail.co', 'gmail.cpm', 'gmail.com.com',
            'yaho.com', 'yahoo.con', 'yahoo.cpm', 'yahool.com',
            'outlok.com', 'outlook.con', 
            'hotmai.com', 'hotmail.con'
        ];
        
        if (in_array($domain, $typoDomains)) {
            return false;
        }
        return true;
    }
}
