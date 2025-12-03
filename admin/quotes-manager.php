<?php
/**
 * FLARE CUSTOM - Gestionnaire de Devis
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Quote.php';

$quoteModel = new Quote();
$action = $_GET['action'] ?? 'list';
$quoteId = $_GET['id'] ?? null;
$quote = null;

if ($quoteId && $action === 'view') {
    $quote = $quoteModel->getById($quoteId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Devis - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --dark: #1d1d1f;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-500: #86868b;
            --success: #34c759;
            --warning: #ff9500;
            --danger: #ff3b30;
            --info: #007aff;
            --sidebar-width: 280px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); }

        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-width); background: #1a1a1c; color: #fff;
            overflow-y: auto; padding: 24px 0;
        }
        .sidebar .logo { padding: 0 24px 24px; font-size: 24px; font-weight: bold; color: var(--primary); }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px; color: rgba(255,255,255,0.7);
            text-decoration: none; transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: #fff; }

        .main-content { margin-left: var(--sidebar-width); padding: 24px 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 28px; }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 8px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: var(--gray-200); color: var(--dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .stat-value { font-size: 32px; font-weight: 700; }
        .stat-label { font-size: 13px; color: var(--gray-500); margin-top: 4px; }

        .stat-pending .stat-value { color: var(--warning); }
        .stat-sent .stat-value { color: var(--info); }
        .stat-accepted .stat-value { color: var(--success); }
        .stat-rejected .stat-value { color: var(--danger); }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { font-size: 12px; text-transform: uppercase; color: var(--gray-500); font-weight: 600; }
        tr:hover { background: var(--gray-100); }
        tr.selected { background: rgba(255,75,38,0.05); }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: rgba(255,149,0,0.1); color: var(--warning); }
        .status-sent { background: rgba(0,122,255,0.1); color: var(--info); }
        .status-accepted { background: rgba(52,199,89,0.1); color: var(--success); }
        .status-rejected { background: rgba(255,59,48,0.1); color: var(--danger); }
        .status-completed { background: rgba(88,86,214,0.1); color: #5856D6; }

        .filters { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .filter-group input, .filter-group select {
            padding: 8px 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px;
        }

        .quote-detail {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .detail-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
        }

        .detail-section h3 {
            font-size: 16px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .detail-label { color: var(--gray-500); }
        .detail-value { font-weight: 600; }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .actions { display: flex; gap: 8px; }

        .price-highlight {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">FLARE CUSTOM</div>
        <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
        <a href="products-manager.php" class="nav-item">📦 Produits</a>
        <a href="categories-manager.php" class="nav-item">📁 Catégories</a>
        <a href="configurator-manager.php" class="nav-item">🔧 Configurateur</a>
        <a href="quotes-manager.php" class="nav-item active">💰 Devis</a>
        <a href="pages-manager.php" class="nav-item">📄 Pages</a>
        <a href="blog-manager.php" class="nav-item">📝 Blog</a>
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>💰 Gestion des Devis</h1>
            <?php if ($action === 'view'): ?>
            <a href="quotes-manager.php" class="btn btn-secondary">← Retour à la liste</a>
            <?php endif; ?>
        </div>

        <div id="alerts"></div>

        <?php if ($action === 'list'): ?>
        <!-- Statistiques -->
        <div class="stats-row" id="statsRow">
            <div class="stat-box">
                <div class="stat-value" id="statTotal">-</div>
                <div class="stat-label">Total devis</div>
            </div>
            <div class="stat-box stat-pending">
                <div class="stat-value" id="statPending">-</div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-box stat-sent">
                <div class="stat-value" id="statSent">-</div>
                <div class="stat-label">Envoyés</div>
            </div>
            <div class="stat-box stat-accepted">
                <div class="stat-value" id="statAccepted">-</div>
                <div class="stat-label">Acceptés</div>
            </div>
            <div class="stat-box stat-rejected">
                <div class="stat-value" id="statRejected">-</div>
                <div class="stat-label">Refusés</div>
            </div>
        </div>

        <!-- Liste des devis -->
        <div class="card">
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Rechercher..." style="min-width: 250px;">
                <select id="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="sent">Envoyé</option>
                    <option value="accepted">Accepté</option>
                    <option value="rejected">Refusé</option>
                    <option value="completed">Terminé</option>
                </select>
                <button class="btn btn-secondary" onclick="loadQuotes()">🔍 Filtrer</button>
                <button class="btn btn-secondary" onclick="exportQuotes()">📤 Exporter CSV</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="quotesTable">
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-500);">
                            Chargement...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php elseif ($action === 'view' && $quote): ?>
        <!-- Détail d'un devis -->
        <div class="quote-detail">
            <div>
                <!-- Informations client -->
                <div class="detail-section">
                    <h3>👤 Informations client</h3>
                    <div class="detail-row">
                        <span class="detail-label">Nom complet</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['client_prenom'] . ' ' . $quote['client_nom']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['client_email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Téléphone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['client_telephone'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Club/Organisation</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['client_club'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fonction</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['client_fonction'] ?? '-'); ?></span>
                    </div>
                </div>

                <!-- Produit et configuration -->
                <div class="detail-section">
                    <h3>📦 Produit demandé</h3>
                    <div class="detail-row">
                        <span class="detail-label">Référence</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['product_reference'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Nom</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['product_nom'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Sport</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['sport'] ?? '-'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type de design</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['design_type'] ?? '-'); ?></span>
                    </div>
                    <?php if ($quote['design_description']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Description du design</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($quote['design_description'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Notes -->
                <?php if ($quote['notes']): ?>
                <div class="detail-section">
                    <h3>📝 Notes</h3>
                    <p><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <!-- Résumé et statut -->
                <div class="detail-section">
                    <h3>📊 Résumé</h3>
                    <div class="detail-row">
                        <span class="detail-label">Référence devis</span>
                        <span class="detail-value"><?php echo htmlspecialchars($quote['reference']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Statut</span>
                        <span class="status-badge status-<?php echo $quote['status']; ?>">
                            <?php echo ucfirst($quote['status']); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Quantité totale</span>
                        <span class="detail-value"><?php echo $quote['total_pieces'] ?? '-'; ?> pièces</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Prix unitaire</span>
                        <span class="detail-value"><?php echo $quote['prix_unitaire'] ? number_format($quote['prix_unitaire'], 2) . ' €' : '-'; ?></span>
                    </div>
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--gray-200);">
                        <div class="detail-label">Total HT</div>
                        <div class="price-highlight"><?php echo $quote['prix_total'] ? number_format($quote['prix_total'], 2) . ' €' : '-'; ?></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="detail-section">
                    <h3>⚡ Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button class="btn btn-success" onclick="updateStatus(<?php echo $quote['id']; ?>, 'sent')">
                            ✉️ Marquer comme envoyé
                        </button>
                        <button class="btn btn-primary" onclick="updateStatus(<?php echo $quote['id']; ?>, 'accepted')">
                            ✅ Marquer comme accepté
                        </button>
                        <button class="btn btn-warning" onclick="updateStatus(<?php echo $quote['id']; ?>, 'rejected')">
                            ❌ Marquer comme refusé
                        </button>
                        <button class="btn btn-danger" onclick="deleteQuote(<?php echo $quote['id']; ?>)">
                            🗑️ Supprimer
                        </button>
                    </div>
                </div>

                <!-- Dates -->
                <div class="detail-section">
                    <h3>📅 Dates</h3>
                    <div class="detail-row">
                        <span class="detail-label">Créé le</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($quote['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Modifié le</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($quote['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const API_URL = '../api/quotes.php';

        // Charger les devis
        async function loadQuotes() {
            const search = document.getElementById('searchInput')?.value || '';
            const status = document.getElementById('statusFilter')?.value || '';

            let url = `${API_URL}?limit=100`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderQuotes(data.data);
                    loadStats();
                }
            } catch (error) {
                showAlert('Erreur de chargement', 'error');
            }
        }

        async function loadStats() {
            try {
                const response = await fetch(`${API_URL}?action=stats`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('statTotal').textContent = data.data.total || 0;
                    document.getElementById('statPending').textContent = data.data.pending || 0;
                    document.getElementById('statSent').textContent = data.data.sent || 0;
                    document.getElementById('statAccepted').textContent = data.data.accepted || 0;
                    document.getElementById('statRejected').textContent = data.data.rejected || 0;
                }
            } catch (error) {
                console.error('Erreur stats:', error);
            }
        }

        function renderQuotes(quotes) {
            const tbody = document.getElementById('quotesTable');

            if (!quotes || quotes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-500);">
                            Aucun devis trouvé
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = quotes.map(q => `
                <tr>
                    <td><strong>${q.reference}</strong></td>
                    <td>${q.client_prenom || ''} ${q.client_nom || ''}</td>
                    <td>${q.client_email || '-'}</td>
                    <td>${q.product_nom || '-'}</td>
                    <td>${q.total_pieces || '-'}</td>
                    <td><strong>${q.prix_total ? q.prix_total + ' €' : '-'}</strong></td>
                    <td><span class="status-badge status-${q.status}">${q.status}</span></td>
                    <td>${formatDate(q.created_at)}</td>
                    <td>
                        <div class="actions">
                            <a href="?action=view&id=${q.id}" class="btn btn-secondary btn-sm">👁️</a>
                            <button class="btn btn-danger btn-sm" onclick="deleteQuote(${q.id})">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('fr-FR');
        }

        async function updateStatus(id, status) {
            try {
                const response = await fetch(API_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Statut mis à jour', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        async function deleteQuote(id) {
            if (!confirm('Supprimer ce devis ?')) return;

            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    showAlert('Devis supprimé', 'success');
                    if (window.location.search.includes('action=view')) {
                        window.location.href = 'quotes-manager.php';
                    } else {
                        loadQuotes();
                    }
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        function exportQuotes() {
            window.open(`${API_URL}?action=export&format=csv`, '_blank');
        }

        function showAlert(message, type) {
            const container = document.getElementById('alerts');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }

        // Charger au démarrage
        <?php if ($action === 'list'): ?>
        loadQuotes();
        <?php endif; ?>
    </script>
</body>
</html>
