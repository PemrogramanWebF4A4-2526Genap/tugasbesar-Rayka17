<?php

require_once __DIR__ . '/route-helper.php';

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    $path = (string) $path;

    if (!preg_match('~^(?:https?:)?//~i', $path)) {
        $path = appUrl($path);
    }

    appRedirect($path);
}

function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        appRedirect(
            appUrl('src/views/public/login.php')
        );
    }
}

function requireRole($role)
{
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != $role) {
        appRedirect(
            appUrl('src/views/public/login.php')
        );
    }
}

function getId($key = 'id')
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : 0;
}