<?php

if (!function_exists('sh_get_role_home')) {
    function sh_get_role_home($role)
    {
        $normalizedRole = strtolower(trim((string)$role));

        switch ($normalizedRole) {
            case 'admin':
            case 'administrateur':
                return 'admin/index.php';

            case 'senior':
                return 'senior/index.php';

            case 'prestataire':
            case 'provider':
                return 'index.php';

            default:
                return 'index.php';
        }
    }
}
