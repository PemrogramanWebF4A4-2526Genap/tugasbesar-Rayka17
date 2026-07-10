<?php

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header("Location: $path");
    exit;
}

function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        header("Location: ../public/login.php");
        exit;
    }
}

function requireRole($role)
{
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != $role) {
        header("Location: ../public/login.php");
        exit;
    }
}

function getId($key = 'id')
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : 0;
}