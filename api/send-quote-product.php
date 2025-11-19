<?php
/**
 * API pour l'envoi de demandes de devis produit (configurateur avancé)
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
function handleQuoteProduct() {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return sendError('Méthode non autorisée', 405);
    }

    // Récupérer les données
    $configJSON = $_POST['configuration'] ?? '';

    if (empty($configJSON)) {
        return sendError('Données manquantes', 400);
    }

    $configuration = json_decode($configJSON, true);

    if (!$configuration) {
        return sendError('Format de données invalide', 400);
    }

    // Valider les données
    $validation = validateData($configuration);
    if ($validation !== true) {
        return sendError($validation, 400);
    }

    // Envoyer les emails
    try {
        // Email au client
        $clientEmailSent = sendClientEmail($configuration);

        // Email à l'admin
        $adminEmailSent = sendAdminEmail($configuration);

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
 * Valide les données de configuration
 */
function validateData($config) {
    // Vérifier l'email
    if (empty($config['contact']['email']) || !filter_var($config['contact']['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Email invalide';
    }

    // Vérifier les champs requis
    $requiredFields = ['prenom', 'nom', 'telephone'];
    foreach ($requiredFields as $field) {
        if (empty($config['contact'][$field])) {
            return "Le champ $field est requis";
        }
    }

    // Vérifier le produit
    if (empty($config['produit']['nom']) || empty($config['produit']['reference'])) {
        return 'Informations produit manquantes';
    }

    // Vérifier les tailles
    if (empty($config['tailles']) || count($config['tailles']) === 0) {
        return 'Aucune taille sélectionnée';
    }

    // Vérifier le design
    if (empty($config['design']['type'])) {
        return 'Type de design non spécifié';
    }

    return true;
}

/**
 * Envoie l'email de confirmation au client
 */
function sendClientEmail($config) {
    $to = $config['contact']['email'];
    $subject = 'Confirmation de votre demande de devis - FLARE CUSTOM';

    $message = generateClientEmailHTML($config);

    $headers = getEmailHeaders(ADMIN_EMAIL);

    return mail($to, $subject, $message, $headers);
}

/**
 * Envoie l'email de notification à l'admin
 */
function sendAdminEmail($config) {
    $to = ADMIN_EMAIL;
    $subject = '🔔 Nouvelle demande de devis produit - ' . $config['contact']['nom'];

    $message = generateAdminEmailHTML($config);

    $headers = getEmailHeaders($config['contact']['email']);

    return mail($to, $subject, $message, $headers);
}

/**
 * Génère le HTML de l'email client
 */
function generateClientEmailHTML($config) {
    $prenom = htmlspecialchars($config['contact']['prenom']);
    $produitNom = htmlspecialchars($config['produit']['nom']);
    $reference = htmlspecialchars($config['produit']['reference']);

    // Calcul quantité totale
    $totalQty = array_sum($config['tailles']);

    // Calcul prix estimé
    $prixBase = floatval($config['produit']['prixBase'] ?? 20);
    $prixUnit = calculateUnitPrice($prixBase, $totalQty);
    $prixTotal = $prixUnit * $totalQty;

    $prixUnitFormatted = number_format($prixUnit, 2, ',', ' ');
    $prixTotalFormatted = number_format($prixTotal, 2, ',', ' ');

    $designType = getDesignTypeLabel($config['design']['type']);
    $genre = isset($config['genre']) ? ucfirst($config['genre']) : 'Non spécifié';

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
                                Nous avons bien reçu votre demande de devis pour votre projet de personnalisation. Notre équipe va l\'étudier et vous recontactera sous <strong>24 heures</strong> avec une proposition détaillée et personnalisée.
                            </p>

                            <!-- Récapitulatif -->
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 25px; margin: 30px 0;">
                                <h3 style="color: #FF4B26; margin: 0 0 20px 0; font-size: 18px;">📋 Récapitulatif de votre demande</h3>

                                <table width="100%" cellpadding="8" cellspacing="0">
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Référence:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $reference . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Produit:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $produitNom . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Type de design:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $designType . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Genre:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $genre . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Quantité totale:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $totalQty . ' pièces</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 14px; border-bottom: 1px solid #e0e0e0;"><strong>Prix unitaire HT:</strong></td>
                                        <td style="color: #333333; font-size: 14px; border-bottom: 1px solid #e0e0e0; text-align: right;">' . $prixUnitFormatted . ' €</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #666666; font-size: 16px; padding-top: 15px;"><strong>Prix total estimé HT:</strong></td>
                                        <td style="color: #FF4B26; font-size: 20px; font-weight: bold; text-align: right; padding-top: 15px;">' . $prixTotalFormatted . ' €</td>
                                    </tr>
                                </table>
                            </div>';

    // Détails des tailles
    if (!empty($config['tailles'])) {
        $html .= '
                            <div style="background-color: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <h4 style="color: #1a1a1a; margin: 0 0 15px 0; font-size: 16px;">📏 Répartition par taille</h4>
                                <table width="100%" cellpadding="6" cellspacing="0">';

        foreach ($config['tailles'] as $taille => $quantite) {
            if ($quantite > 0) {
                $html .= '
                                    <tr>
                                        <td style="color: #666; font-size: 14px; padding: 4px 0;">' . htmlspecialchars($taille) . '</td>
                                        <td style="color: #333; font-size: 14px; font-weight: 600; text-align: right; padding: 4px 0;">' . intval($quantite) . ' pièces</td>
                                    </tr>';
            }
        }

        $html .= '
                                </table>
                            </div>';
    }

    // Personnalisation
    $perso = $config['personnalisation'];
    if (!empty($perso['couleurPrincipale']) || !empty($perso['numeros']) || !empty($perso['noms'])) {
        $html .= '
                            <div style="background-color: #fff3e6; border-left: 4px solid #FF4B26; padding: 20px; margin: 20px 0;">
                                <h4 style="color: #FF4B26; margin: 0 0 15px 0; font-size: 16px;">🎨 Détails de personnalisation</h4>';

        if (!empty($perso['couleurPrincipale'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Couleur principale:</strong> ' . htmlspecialchars($perso['couleurPrincipale']) . '</p>';
        }
        if (!empty($perso['couleurSecondaire'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Couleur secondaire:</strong> ' . htmlspecialchars($perso['couleurSecondaire']) . '</p>';
        }
        if (!empty($perso['numeros'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Numéros:</strong> Oui</p>';
            if (!empty($perso['numerosStyle'])) {
                $html .= '<p style="color: #666; margin: 5px 0 5px 20px; font-size: 13px;">' . htmlspecialchars($perso['numerosStyle']) . '</p>';
            }
        }
        if (!empty($perso['noms'])) {
            $html .= '<p style="color: #333333; margin: 5px 0;"><strong>Noms:</strong> Oui</p>';
            if (!empty($perso['nomsStyle'])) {
                $html .= '<p style="color: #666; margin: 5px 0 5px 20px; font-size: 13px;">' . htmlspecialchars($perso['nomsStyle']) . '</p>';
            }
        }
        if (!empty($perso['remarques'])) {
            $html .= '<p style="color: #333333; margin: 15px 0 5px 0;"><strong>Remarques:</strong></p>';
            $html .= '<p style="color: #666; margin: 5px 0;">' . nl2br(htmlspecialchars($perso['remarques'])) . '</p>';
        }

        $html .= '</div>';
    }

    $html .= '
                            <div style="margin: 30px 0;">
                                <p style="color: #333333; line-height: 1.6; margin: 0 0 10px 0;">
                                    <strong>Prochaines étapes:</strong>
                                </p>
                                <ol style="color: #333333; line-height: 1.8; padding-left: 20px;">
                                    <li>Notre équipe étudie votre demande en détail</li>
                                    <li>Nous vous recontactons sous 24h avec un devis détaillé</li>
                                    <li>Validation du devis et création de vos visuels</li>
                                    <li>Lancement de la production après votre accord</li>
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
function generateAdminEmailHTML($config) {
    $totalQty = array_sum($config['tailles']);
    $prixBase = floatval($config['produit']['prixBase'] ?? 20);
    $prixUnit = calculateUnitPrice($prixBase, $totalQty);
    $prixTotal = $prixUnit * $totalQty;

    $html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de devis produit</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden;">

                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px; text-align: center;">
                            <h1 style="color: #FF4B26; margin: 0; font-size: 24px;">🔔 Nouvelle demande de devis produit</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px;">

                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">👤 Informations client</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Nom:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['contact']['prenom']) . ' ' . htmlspecialchars($config['contact']['nom']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Email:</strong></td>
                                    <td style="color: #333; font-size: 14px;"><a href="mailto:' . htmlspecialchars($config['contact']['email']) . '">' . htmlspecialchars($config['contact']['email']) . '</a></td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Téléphone:</strong></td>
                                    <td style="color: #333; font-size: 14px;"><a href="tel:' . htmlspecialchars($config['contact']['telephone']) . '">' . htmlspecialchars($config['contact']['telephone']) . '</a></td>
                                </tr>';

    if (!empty($config['contact']['club'])) {
        $html .= '
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Club/Entreprise:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['contact']['club']) . '</td>
                                </tr>';
    }

    if (!empty($config['contact']['fonction'])) {
        $html .= '
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Fonction:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['contact']['fonction']) . '</td>
                                </tr>';
    }

    $html .= '
                            </table>

                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">📦 Détails du produit</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Référence:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['produit']['reference']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Produit:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['produit']['nom']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Sport:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . htmlspecialchars($config['produit']['sport']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Genre:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . (isset($config['genre']) ? ucfirst($config['genre']) : 'Non spécifié') . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Type de design:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . getDesignTypeLabel($config['design']['type']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Quantité totale:</strong></td>
                                    <td style="color: #333; font-size: 14px; font-weight: bold;">' . $totalQty . ' pièces</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 14px;"><strong>Prix unitaire HT:</strong></td>
                                    <td style="color: #333; font-size: 14px;">' . number_format($prixUnit, 2, ',', ' ') . ' €</td>
                                </tr>
                                <tr>
                                    <td style="color: #666; font-size: 16px;"><strong>Prix total HT:</strong></td>
                                    <td style="color: #FF4B26; font-size: 18px; font-weight: bold;">' . number_format($prixTotal, 2, ',', ' ') . ' €</td>
                                </tr>
                            </table>';

    // Répartition des tailles
    if (!empty($config['tailles'])) {
        $html .= '
                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">📏 Répartition des tailles</h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                                <table width="100%" cellpadding="6" cellspacing="0">';

        foreach ($config['tailles'] as $taille => $quantite) {
            if ($quantite > 0) {
                $html .= '
                                    <tr>
                                        <td style="color: #666; font-size: 14px;">' . htmlspecialchars($taille) . '</td>
                                        <td style="color: #333; font-size: 14px; font-weight: 600; text-align: right;">' . intval($quantite) . ' pcs</td>
                                    </tr>';
            }
        }

        $html .= '
                                </table>
                            </div>';
    }

    // Options produit
    if (!empty($config['options'])) {
        $html .= '
                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">⚙️ Options produit</h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;">';

        if (!empty($config['options']['col'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Col:</strong> ' . htmlspecialchars($config['options']['col']) . '</p>';
        }
        if (!empty($config['options']['manches'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Manches:</strong> ' . htmlspecialchars($config['options']['manches']) . '</p>';
        }
        if (isset($config['options']['poches'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Poches:</strong> ' . ($config['options']['poches'] ? 'Oui' : 'Non') . '</p>';
        }
        if (!empty($config['options']['fermeture'])) {
            $html .= '<p style="margin: 5px 0;"><strong>Fermeture:</strong> ' . htmlspecialchars($config['options']['fermeture']) . '</p>';
        }

        $html .= '</div>';
    }

    // Personnalisation
    $perso = $config['personnalisation'];
    $html .= '
                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">🎨 Personnalisation</h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;">';

    if (!empty($perso['couleurPrincipale'])) {
        $html .= '<p style="margin: 5px 0;"><strong>Couleur principale:</strong> ' . htmlspecialchars($perso['couleurPrincipale']) . '</p>';
    }
    if (!empty($perso['couleurSecondaire'])) {
        $html .= '<p style="margin: 5px 0;"><strong>Couleur secondaire:</strong> ' . htmlspecialchars($perso['couleurSecondaire']) . '</p>';
    }
    if (!empty($perso['couleurTertiaire'])) {
        $html .= '<p style="margin: 5px 0;"><strong>Couleur tertiaire:</strong> ' . htmlspecialchars($perso['couleurTertiaire']) . '</p>';
    }
    if (!empty($perso['numeros'])) {
        $html .= '<p style="margin: 5px 0;"><strong>Numéros:</strong> Oui' . (!empty($perso['numerosStyle']) ? ' - ' . htmlspecialchars($perso['numerosStyle']) : '') . '</p>';
    }
    if (!empty($perso['noms'])) {
        $html .= '<p style="margin: 5px 0;"><strong>Noms:</strong> Oui' . (!empty($perso['nomsStyle']) ? ' - ' . htmlspecialchars($perso['nomsStyle']) : '') . '</p>';
    }
    if (!empty($perso['remarques'])) {
        $html .= '<p style="margin: 15px 0 5px 0;"><strong>Remarques:</strong></p>';
        $html .= '<p style="margin: 5px 0;">' . nl2br(htmlspecialchars($perso['remarques'])) . '</p>';
    }

    $html .= '</div>';

    // Description du design
    if (!empty($config['design']['description'])) {
        $html .= '
                            <h3 style="color: #FF4B26; margin: 0 0 15px 0;">💬 Description du projet</h3>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                                <p style="margin: 0; color: #333;">' . nl2br(htmlspecialchars($config['design']['description'])) . '</p>
                            </div>';
    }

    $html .= '
                            <div style="background-color: #fff3e6; padding: 20px; border-radius: 8px; border-left: 4px solid #FF4B26;">
                                <p style="margin: 0; color: #333; font-weight: bold;">⚡ Action requise: Répondre au client sous 24h</p>
                                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">Préparer un devis détaillé avec les fichiers de personnalisation</p>
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
 * Calcule le prix unitaire selon la quantité
 */
function calculateUnitPrice($prixBase, $quantite) {
    if ($quantite >= 500) return $prixBase * 0.65;
    if ($quantite >= 250) return $prixBase * 0.70;
    if ($quantite >= 100) return $prixBase * 0.75;
    if ($quantite >= 50) return $prixBase * 0.80;
    if ($quantite >= 20) return $prixBase * 0.85;
    if ($quantite >= 10) return $prixBase * 0.90;
    if ($quantite >= 5) return $prixBase * 0.95;
    return $prixBase;
}

/**
 * Retourne le label du type de design
 */
function getDesignTypeLabel($type) {
    $labels = [
        'flare' => 'Design par FLARE',
        'client' => 'Fichiers client',
        'template' => 'Template prédéfini'
    ];
    return $labels[$type] ?? 'Non spécifié';
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
handleQuoteProduct();
