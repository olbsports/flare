<?php
/**
 * FLARE CUSTOM - Logout
 * Déconnexion de l'administration
 */

session_start();

// Détruire toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Détruire la session
session_destroy();

// Redirection vers la page de connexion
header('Location: login.php?logout=success');
exit;
