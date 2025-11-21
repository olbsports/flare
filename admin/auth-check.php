<?php
/**
 * Protection des pages admin - À inclure en haut de chaque page
 */

session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

// Récupère les infos user pour la page
$current_user = $_SESSION['admin_user'];
