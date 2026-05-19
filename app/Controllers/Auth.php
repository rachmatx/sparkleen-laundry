<?php

// app/Controllers/Auth.php

namespace App\Controllers;

use App\Models\MKaryawan;

class Auth extends BaseController
{
    protected $karyawanModel;

    public function __construct()
    {
        $this->karyawanModel = new MKaryawan();
    }

    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $throttle = service('loginThrottle');
        $ip       = $this->request->getIPAddress();
        $blocked  = $throttle->isBlocked($ip);

        return view('auth/login', [
            'loginBlocked'        => $blocked,
            'loginBlockedMessage' => $blocked ? $throttle->getBlockedMessage($ip) : '',
        ]);
    }

    public function doLogin()
    {
        $throttle   = service('loginThrottle');
        $ip         = $this->request->getIPAddress();
        $identifier = trim((string) $this->request->getPost('username'));
        $password   = $this->request->getPost('password');

        if ($throttle->isBlocked($ip, $identifier)) {
            session()->setFlashdata('error', $throttle->getBlockedMessage($ip, $identifier));

            return redirect()->to('/login');
        }

        if ($identifier === '' || empty($password)) {
            session()->setFlashdata('error', 'Username/email dan password wajib diisi.');

            return redirect()->to('/login');
        }

        $user = $this->karyawanModel->getUserByIdentifier($identifier);

        if ($user) {
            if ($user->is_aktif == 0) {
                session()->setFlashdata('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi Admin.');

                return redirect()->to('/login');
            }

            if (password_verify($password, $user->password)) {
                $throttle->clear($ip, $identifier);

                session()->set([
                    'id_karyawan'   => $user->id_karyawan,
                    'nama_karyawan' => $user->nama_karyawan,
                    'username'      => $user->username,
                    'email'         => $user->email ?? '',
                    'level'         => $user->level,
                    'isLoggedIn'    => true,
                ]);

                session()->setFlashdata('success', 'Selamat datang, ' . $user->nama_karyawan . '!');

                return redirect()->to('/dashboard');
            }
        }

        $throttle->recordFailure($ip, $identifier);

        if ($throttle->isBlocked($ip, $identifier)) {
            session()->setFlashdata('error', $throttle->getBlockedMessage($ip, $identifier));
        } else {
            session()->setFlashdata('error', $throttle->getFailureMessage($ip, $identifier));
        }

        return redirect()->to('/login');
    }

    public function logout()
    {
        session()->destroy();
        session()->setFlashdata('success', 'Anda telah berhasil logout.');

        return redirect()->to('/login');
    }
}
