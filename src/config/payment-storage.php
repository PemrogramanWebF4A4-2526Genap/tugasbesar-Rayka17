<?php

if (!function_exists('paymentStorageDirectory')) {
    function paymentStorageDirectory(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    }
}

if (!function_exists('paymentUploadDirectory')) {
    function paymentUploadDirectory(): string
    {
        return dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'payment-proofs';
    }
}

if (!function_exists('paymentEnsureDirectory')) {
    function paymentEnsureDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return @mkdir($directory, 0755, true) || is_dir($directory);
    }
}

if (!function_exists('paymentEnsureStorage')) {
    function paymentEnsureStorage(): bool
    {
        $storageReady = paymentEnsureDirectory(paymentStorageDirectory());
        $uploadReady = paymentEnsureDirectory(paymentUploadDirectory());

        return $storageReady && $uploadReady;
    }
}

if (!function_exists('paymentJsonRead')) {
    function paymentJsonRead(string $fileName): array
    {
        if (!paymentEnsureStorage()) {
            return [];
        }

        $path = paymentStorageDirectory()
            . DIRECTORY_SEPARATOR
            . basename($fileName);

        if (!is_file($path)) {
            return [];
        }

        $handle = @fopen($path, 'rb');

        if (!$handle) {
            return [];
        }

        $contents = '';

        if (flock($handle, LOCK_SH)) {
            $contents = stream_get_contents($handle) ?: '';
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        if ($contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('paymentJsonWrite')) {
    function paymentJsonWrite(string $fileName, array $data): bool
    {
        if (!paymentEnsureStorage()) {
            return false;
        }

        $path = paymentStorageDirectory()
            . DIRECTORY_SEPARATOR
            . basename($fileName);

        $handle = @fopen($path, 'c+b');

        if (!$handle) {
            return false;
        }

        $written = false;

        if (flock($handle, LOCK_EX)) {
            $json = json_encode(
                $data,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

            if ($json !== false) {
                rewind($handle);
                ftruncate($handle, 0);
                $written = fwrite($handle, $json . PHP_EOL) !== false;
                fflush($handle);
            }

            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $written;
    }
}

if (!function_exists('paymentCleanText')) {
    function paymentCleanText($value, int $maxLength = 120): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('paymentGetAllBankAccounts')) {
    function paymentGetAllBankAccounts(): array
    {
        return paymentJsonRead('mitra-bank-accounts.json');
    }
}

if (!function_exists('paymentGetMitraBankAccount')) {
    function paymentGetMitraBankAccount(int $mitraId): array
    {
        $accounts = paymentGetAllBankAccounts();
        $key = (string) $mitraId;
        $account = $accounts[$key] ?? [];

        return [
            'mitra_id' => $mitraId,
            'bank_name' => (string) ($account['bank_name'] ?? ''),
            'account_number' => (string) ($account['account_number'] ?? ''),
            'account_holder' => (string) ($account['account_holder'] ?? ''),
            'updated_at' => (string) ($account['updated_at'] ?? '')
        ];
    }
}

if (!function_exists('paymentBankAccountComplete')) {
    function paymentBankAccountComplete(array $account): bool
    {
        return trim((string) ($account['bank_name'] ?? '')) !== ''
            && trim((string) ($account['account_number'] ?? '')) !== ''
            && trim((string) ($account['account_holder'] ?? '')) !== '';
    }
}

if (!function_exists('paymentSaveMitraBankAccount')) {
    function paymentSaveMitraBankAccount(
        int $mitraId,
        string $bankName,
        string $accountNumber,
        string $accountHolder
    ): bool {
        if ($mitraId < 1) {
            return false;
        }

        $bankName = paymentCleanText($bankName, 80);
        $accountNumber = preg_replace('/[^0-9A-Za-z+\-. ]/', '', $accountNumber) ?: '';
        $accountNumber = paymentCleanText($accountNumber, 60);
        $accountHolder = paymentCleanText($accountHolder, 120);

        if ($bankName === '' || $accountNumber === '' || $accountHolder === '') {
            return false;
        }

        $accounts = paymentGetAllBankAccounts();
        $accounts[(string) $mitraId] = [
            'mitra_id' => $mitraId,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_holder' => $accountHolder,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return paymentJsonWrite('mitra-bank-accounts.json', $accounts);
    }
}

if (!function_exists('paymentGetAllProofs')) {
    function paymentGetAllProofs(): array
    {
        return paymentJsonRead('payment-proofs.json');
    }
}

if (!function_exists('paymentGetProof')) {
    function paymentGetProof(int $orderId): ?array
    {
        $proofs = paymentGetAllProofs();
        $proof = $proofs[(string) $orderId] ?? null;

        return is_array($proof) ? $proof : null;
    }
}

if (!function_exists('paymentProofAbsolutePath')) {
    function paymentProofAbsolutePath(array $proof): string
    {
        $fileName = basename((string) ($proof['file_name'] ?? ''));

        if ($fileName === '') {
            return '';
        }

        return paymentUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $fileName;
    }
}

if (!function_exists('paymentValidateProofUpload')) {
    function paymentValidateProofUpload(?array $file): array
    {
        if (
            !$file
            || !isset($file['error'])
            || (int) $file['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return [
                'ok' => true,
                'has_file' => false,
                'error' => ''
            ];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Upload bukti pembayaran gagal.'
            ];
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size < 1 || $size > (5 * 1024 * 1024)) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Ukuran bukti pembayaran maksimal 5 MB.'
            ];
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'File bukti pembayaran tidak valid.'
            ];
        }

        $mimeType = '';

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = (string) $finfo->file($temporaryPath);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = (string) mime_content_type($temporaryPath);
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Bukti pembayaran harus berupa JPG, PNG, atau WEBP.'
            ];
        }

        return [
            'ok' => true,
            'has_file' => true,
            'error' => '',
            'mime_type' => $mimeType,
            'extension' => $allowedMimeTypes[$mimeType],
            'size' => $size,
            'temporary_path' => $temporaryPath,
            'original_name' => paymentCleanText(
                basename((string) ($file['name'] ?? 'bukti-pembayaran')),
                180
            )
        ];
    }
}

if (!function_exists('paymentSaveProofUpload')) {
    function paymentSaveProofUpload(
        int $orderId,
        int $userId,
        int $mitraId,
        ?array $file
    ): array {
        $validation = paymentValidateProofUpload($file);

        if (!$validation['ok']) {
            return $validation;
        }

        if (!$validation['has_file']) {
            return [
                'ok' => true,
                'has_file' => false,
                'error' => ''
            ];
        }

        if (!paymentEnsureStorage()) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Folder penyimpanan bukti pembayaran tidak dapat dibuat.'
            ];
        }

        try {
            $randomPart = bin2hex(random_bytes(8));
        } catch (Throwable $throwable) {
            $randomPart = str_replace('.', '', uniqid('', true));
        }

        $fileName = 'order-'
            . $orderId
            . '-'
            . date('YmdHis')
            . '-'
            . $randomPart
            . '.'
            . $validation['extension'];

        $destination = paymentUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $fileName;

        if (!@move_uploaded_file($validation['temporary_path'], $destination)) {
            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Bukti pembayaran gagal disimpan di server.'
            ];
        }

        @chmod($destination, 0644);

        $proofs = paymentGetAllProofs();
        $oldProof = $proofs[(string) $orderId] ?? null;

        $proof = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'mitra_id' => $mitraId,
            'file_name' => $fileName,
            'original_name' => $validation['original_name'],
            'mime_type' => $validation['mime_type'],
            'size' => $validation['size'],
            'uploaded_at' => date('Y-m-d H:i:s'),
            'verification_status' => 'pending',
            'verification_note' => '',
            'verified_by' => 0,
            'verified_by_name' => '',
            'verified_at' => ''
        ];

        $proofs[(string) $orderId] = $proof;

        if (!paymentJsonWrite('payment-proofs.json', $proofs)) {
            @unlink($destination);

            return [
                'ok' => false,
                'has_file' => true,
                'error' => 'Data bukti pembayaran gagal disimpan.'
            ];
        }

        if (is_array($oldProof)) {
            $oldPath = paymentProofAbsolutePath($oldProof);

            if ($oldPath !== '' && $oldPath !== $destination && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return [
            'ok' => true,
            'has_file' => true,
            'error' => '',
            'proof' => $proof
        ];
    }
}


if (!function_exists('paymentNormalizeVerificationStatus')) {
    function paymentNormalizeVerificationStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['pending', 'valid', 'invalid'];

        return in_array($status, $allowed, true) ? $status : 'pending';
    }
}

if (!function_exists('paymentProofVerificationStatus')) {
    function paymentProofVerificationStatus(?array $proof): string
    {
        if (!$proof) {
            return 'not_uploaded';
        }

        return paymentNormalizeVerificationStatus(
            (string) ($proof['verification_status'] ?? 'pending')
        );
    }
}

if (!function_exists('paymentSaveAdminVerification')) {
    function paymentSaveAdminVerification(
        int $orderId,
        string $status,
        int $adminId,
        string $adminName,
        string $note = ''
    ): bool {
        if ($orderId < 1 || $adminId < 1) {
            return false;
        }

        $status = paymentNormalizeVerificationStatus($status);
        $proofs = paymentGetAllProofs();
        $key = (string) $orderId;
        $proof = $proofs[$key] ?? null;

        if (!is_array($proof)) {
            return false;
        }

        $proof['verification_status'] = $status;
        $proof['verification_note'] = paymentCleanText($note, 500);
        $proof['verified_by'] = $adminId;
        $proof['verified_by_name'] = paymentCleanText($adminName, 120);
        $proof['verified_at'] = date('Y-m-d H:i:s');

        if ($status === 'pending') {
            $proof['verification_note'] = '';
            $proof['verified_by'] = 0;
            $proof['verified_by_name'] = '';
            $proof['verified_at'] = '';
        }

        $proofs[$key] = $proof;

        return paymentJsonWrite('payment-proofs.json', $proofs);
    }
}

paymentEnsureStorage();
