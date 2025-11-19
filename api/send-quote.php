<?php
/**
 * API pour l'envoi de demandes de devis
 * FLARE CUSTOM - 2025
 */

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration email
define('ADMIN_EMAIL', 'contact@flare-custom.com');
define('SITE_NAME', 'FLARE CUSTOM');
define('SITE_URL', 'https://flare-custom.com');

/**
 * Fonction principale
 */
function handleQuoteRequest() {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return sendError('Méthode non autorisée', 405);
    }

    // Récupérer les données
    $configJSON = $_POST['configuration'] ?? '';
    $recapJSON = $_POST['recapitulatif'] ?? '';

    if (empty($configJSON) || empty($recapJSON)) {
        return sendError('Données manquantes', 400);
    }

    $configuration = json_decode($configJSON, true);
    $recapitulatif = json_decode($recapJSON, true);

    if (!$configuration || !$recapitulatif) {
        return sendError('Format de données invalide', 400);
    }

    // Valider les données
    $validation = validateData($recapitulatif);
    if ($validation !== true) {
        return sendError($validation, 400);
    }

    // Envoyer les emails
    try {
        // Email au client
        $clientEmailSent = sendClientEmail($recapitulatif);

        // Email à l'admin
        $adminEmailSent = sendAdminEmail($recapitulatif, $configuration);

        if ($clientEmailSent && $adminEmailSent) {
            return sendSuccess('Demande de devis envoyée avec succès');
        } else {
            return sendError('Erreur lors de l\'envoi des emails', 500);
        }
    } catch (Exception $e) {
        return sendError('Erreur serveur: ' . $e->getMessage(), 500);
    }
}

/**
 * Valide les données du récapitulatif
 */
function validateData($recap) {
    // Vérifier l'email
    if (empty($recap['contact']['email']) || !filter_var($recap['contact']['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Email invalide';
    }

    // Vérifier les champs requis
    $requiredFields = ['prenom', 'nom', 'telephone'];
    foreach ($requiredFields as $field) {
        if (empty($recap['contact'][$field])) {
            return "Le champ $field est requis";
        }
    }

    // Vérifier le produit
    if (empty($recap['produit']['nom']) || empty($recap['produit']['reference'])) {
        return 'Informations produit manquantes';
    }

    // Vérifier la quantité
    if (empty($recap['quantite']) || $recap['quantite'] < 1) {
        return 'Quantité invalide';
    }

    return true;
}

/**
 * Envoie l'email de confirmation au client
 */
function sendClientEmail($recap) {
    $to = $recap['contact']['email'];
    $subject = 'Confirmation de votre demande de devis - FLARE CUSTOM';

    $message = generateClientEmailHTML($recap);

    $headers = getEmailHeaders(ADMIN_EMAIL);

    return mail($to, $subject, $message, $headers);
}

/**
 * Envoie l'email de notification à l'admin
 */
function sendAdminEmail($recap, $config) {
    $to = ADMIN_EMAIL;
    $subject = '🔔 Nouvelle demande de devis - ' . $recap['contact']['nom'];

    $message = generateAdminEmailHTML($recap, $config);

    $headers = getEmailHeaders($recap['contact']['email']);

    return mail($to, $subject, $message, $headers);
}

/**
 * Génère le HTML de l'email client
 */
function generateClientEmailHTML($recap) {
    $prenom = htmlspecialchars($recap['contact']['prenom']);
    $produitNom = htmlspecialchars($recap['produit']['nom']);
    $reference = htmlspecialchars($recap['produit']['reference']);
    $quantite = intval($recap['quantite']);
    $prixUnit = number_format($recap['prix']['unitaire'], 2, ',', ' ');
    $prixTotal = number_format($recap['prix']['total'], 2, ',', ' ');
    $sport = htmlspecialchars($recap['produit']['sport']);
    $genre = htmlspecialchars($recap['produit']['genre']);
    $tissu = htmlspecialchars($recap['produit']['tissu']);
    $grammage = htmlspecialchars($recap['produit']['grammage']);
    $date = htmlspecialchars($recap['date']);

    $photoUrl = !empty($recap['produit']['photo']) ? htmlspecialchars($recap['produit']['photo']) : '';

    $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de devis</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">

                    <!-- En-tête -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">FLARE CUSTOM</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Confirmation de votre demande de devis</p>
                        </td>
                    </tr>

                    <!-- Contenu -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1a1a; margin: 0 0 20px 0; font-size: 22px;">Bonjour ' . $prenom . ',</h2>

                            <p style="color: #333333; line-height: 1.6; margin: 0 0 20px 0;">
                                Nous avons bien reçu votre demande de devis. Notre équipe va l\'étudier et vous recontactera sous <strong>24 heures</strong> avec une proposition personnalisée.
                            </p>

                            <!-- Récapitulatif -->
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 25px; margin: 30px 0;">
                                <h3 style="color: #FF4B26; margin: 0 0 20px 0; font-size: 18px;">📋 Récapitulatif de votre demande</h3>

                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Date:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $date . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Référence:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $reference . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Produit:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $produitNom . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Sport:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $sport . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Genre:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $genre . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Tissu:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $tissu . ' - ' . $grammage . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Quantité:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $quantite . ' pièces</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Prix unitaire HT:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $prixUnit . ' €</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 16px; padding-top: 15px;"><strong>Prix total HT:</strong></td>
                                        <td style="color: #FF4B26; font-size: 20px; font-weight: bold; text-align: right; padding-top: 15px;">' . $prixTotal . ' €</td>
                                    </tr>
                                </table>
                            </div>';

    // Personnalisation
    if (!empty($recap['personnalisation']['couleurs']) ||
        !empty($recap['personnalisation']['logos']) ||
        !empty($recap['personnalisation']['textes'])) {

        $html .= '
                            <div style="background-color: #fff3e6; border-left: 4px solid #FF4B26; padding: 20px; margin: 20px 0;">
                                <h4 style="color: #FF4B26; margin: 0 0 15px 0; font-size: 16px;">🎨 Détails de personnalisation</h4>';

        if (!empty($recap['personnalisation']['couleurs'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Couleurs:</strong> ' . htmlspecialchars($recap['personnalisation']['couleurs']) . '</p>';
        }
        if (!empty($recap['personnalisation']['logos'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Logos:</strong> ' . htmlspecialchars($recap['personnalisation']['logos']) . '</p>';
        }
        if (!empty($recap['personnalisation']['textes'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Textes/Numéros:</strong> ' . htmlspecialchars($recap['personnalisation']['textes']) . '</p>';
        }
        if (!empty($recap['personnalisation']['remarques'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Remarques:</strong> ' . htmlspecialchars($recap['personnalisation']['remarques']) . '</p>';
        }

        $html .= '</div>';
    }

    $html .= '
                            <div style="margin: 30px 0;">
                                <p style="color: #333333; line-height: 1.6; margin: 0 0 10px 0;">
                                    <strong>Prochaines étapes:</strong>
                                </p>
                                <ol style="color: #333333; line-height: 1.8; padding-left: 20px;">
                                    <li>Notre équipe étudie votre demande</li>
                                    <li>Nous vous recontactons sous 24h avec un devis détaillé</li>
                                    <li>Validation du devis et lancement de la production</li>
                                    <li>Livraison de vos équipements personnalisés</li>
                                </ol>
                            </div>

                            <div style="background-color: #e8f5e9; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center;">
                                <p style="color: #2e7d32; margin: 0; font-size: 16px; font-weight: bold;">
                                    ✅ Une question ? Contactez-nous !
                                </p>
                                <p style="color: #333333; margin: 10px 0 0 0; font-size: 14px;">
                                    📧 <a href="mailto:contact@flare-custom.com" style="color: #FF4B26; text-decoration: none;">contact@flare-custom.com</a><br>
                                    📱 <a href="https://wa.me/359885813134" style="color: #FF4B26; text-decoration: none;">+359 885 813 134</a>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px; text-align: center;">
                            <p style="color: #ffffff; margin: 0 0 10px 0; font-size: 18px; font-weight: bold;">FLARE CUSTOM</p>
                            <p style="color: #999999; margin: 0; font-size: 14px;">Équipements sportifs personnalisés de qualité professionnelle</p>
                            <p style="color: #999999; margin: 15px 0 0 0; font-size: 12px;">
                                <a href="' . SITE_URL . '" style="color: #FF4B26; text-decoration: none;">www.flare-custom.com</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
    ';

    return $html;
}

/**
 * Génère le HTML de l'email admin
 */
function generateAdminEmailHTML($recap, $config) {
    $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de devis</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden;">

                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px; text-align: center;">
                            <h1 style="color: #FF4B26; margin: 0; font-size: 24px;">🔔 Nouvelle demande de devis</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px;">

                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">👤 Informations client</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Nom:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['contact']['prenom']) . ' ' . htmlspecialchars($recap['contact']['nom']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Email:</strong></td>
                                    <td style="color: #333; font-size: 14px;"><a href="mailto:' . htmlspecialchars($recap['contact']['email']) . '">' . htmlspecialchars($recap['contact']['email']) . '</a></td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Téléphone:</strong></td>
                                    <td style="color: #333; font-size: 14px;"><a href="tel:' . htmlspecialchars($recap['contact']['telephone']) . '">' . htmlspecialchars($recap['contact']['telephone']) . '</a></td>
                                </tr>';

    if (!empty($recap['contact']['club'])) {
        $html .= '
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Club:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['contact']['club']) . '</td>
                                </tr>';
    }

    $html .= '
                            </table>

                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">📦 Détails de la commande</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Référence:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['produit']['reference']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Produit:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['produit']['nom']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Sport:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['produit']['sport']) . ' - ' . htmlspecialchars($recap['produit']['genre']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Tissu:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($recap['produit']['tissu']) . ' - ' . htmlspecialchars($recap['produit']['grammage']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Quantité:</strong></td>
                                    <td style="color: #333; font-size: 14px; font-weight: bold;">' . intval($recap['quantite']) . ' pièces</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Prix unitaire HT:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . number_format($recap['prix']['unitaire'], 2, ',', ' ') . ' €</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 16px;"><strong>Prix total HT:</strong></td>
                                    <td style="color: #FF4B26; font-size: 18px; font-weight: bold;">' . number_format($recap['prix']['total'], 2, ',', ' ') . ' €</td>
                                </tr>
                            </table>';

    // Personnalisation
    if (!empty($recap['personnalisation']['design']) ||
        !empty($recap['personnalisation']['couleurs']) ||
        !empty($recap['personnalisation']['logos'])) {

        $html .= '
                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">🎨 Personnalisation</h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                                <p style="margin: 5px 0;"><strong>Service design:</strong> ' . ($recap['personnalisation']['design'] ? 'Oui' : 'Non') . '</p>';

        if (!empty($recap['personnalisation']['couleurs'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Couleurs:</strong> ' . htmlspecialchars($recap['personnalisation']['couleurs']) . '</p>';
        }
        if (!empty($recap['personnalisation']['logos'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Logos:</strong> ' . htmlspecialchars($recap['personnalisation']['logos']) . '</p>';
        }
        if (!empty($recap['personnalisation']['textes'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Textes:</strong> ' . htmlspecialchars($recap['personnalisation']['textes']) . '</p>';
        }
        if (!empty($recap['personnalisation']['remarques'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Remarques:</strong> ' . htmlspecialchars($recap['personnalisation']['remarques']) . '</p>';
        }

        $html .= '</div>';
    }

    $html .= '
                            <div style="background-color: #fff3e6; padding: 20px; border-radius: 8px; border-left: 4px solid #FF4B26;">
                                <p style="margin: 0; color: #333; font-weight: bold;">⚡ Action requise: Répondre au client sous 24h</p>
                            </div>

                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
    ';

    return $html;
}

/**
 * Retourne les headers pour les emails HTML
 */
function getEmailHeaders($from) {
    return "MIME-Version: 1.0\r\n" .
           "Content-Type: text/html; charset=UTF-8\r\n" .
           "From: " . SITE_NAME . " <" . ADMIN_EMAIL . ">\r\n" .
           "Reply-To: " . $from . "\r\n" .
           "X-Mailer: PHP/" . phpversion();
}

/**
 * Envoie une réponse de succès
 */
function sendSuccess($message) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    exit;
}

/**
 * Envoie une réponse d'erreur
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

// Exécuter la fonction principale
handleQuoteRequest();
