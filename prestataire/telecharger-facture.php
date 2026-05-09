<?php
// Cette page génère une facture en HTML imprimable.
// Le prestataire clique sur "Imprimer" (ou Ctrl+P) et choisit "Enregistrer en PDF".
// La fenêtre s'ouvre automatiquement en mode impression grâce au JS en bas de page.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../include/callapi.php';

// Sécurité : seul un prestataire connecté peut accéder à sa propre facture
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role'] ?? '') !== 'prestataire') {
    header('Location: ../login.php');
    exit;
}

$idFacture = trim($_GET['id'] ?? '');
$idUser    = $_SESSION['user']['id_user'] ?? '';

if ($idFacture === '' || !$pdo instanceof PDO) {
    exit('Facture introuvable.');
}

// On récupère la facture + les infos du prestataire en une seule requête
// La clause WHERE id_user vérifie que la facture appartient bien au prestataire connecté
$stmt = $pdo->prepare(
    "SELECT pi.id_invoice, pi.month_label, pi.amount, pi.status, pi.generated_at,
            u.first_name, u.last_name, u.email, u.phone, u.company_name, u.siret_number, u.iban,
            pp.paid_at, pp.status AS payment_status
     FROM provider_invoices pi
     JOIN users u ON u.id_user = pi.id_user
     LEFT JOIN provider_payments pp ON pp.id_invoice = pi.id_invoice
     WHERE pi.id_invoice = :id AND pi.id_user = :uid
     LIMIT 1"
);
$stmt->execute(['id' => $idFacture, 'uid' => $idUser]);
$facture = $stmt->fetch();

if (!$facture) {
    exit('Facture introuvable ou accès refusé.');
}

// Numéro de facture lisible : "FACT-2026-05-XXXX"
$numeroFacture = 'FACT-' . str_replace('-', '-', $facture['month_label']) . '-' . strtoupper(substr($facture['id_invoice'], -4));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?= htmlspecialchars($numeroFacture) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; padding: 40px; }

        .entete { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .entete h1 { font-size: 28px; color: #2c7a4b; }
        .entete .infos-societe { text-align: right; color: #555; line-height: 1.6; }

        .bloc-adresses { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .bloc-adresses .bloc { border: 1px solid #ddd; padding: 12px 16px; width: 48%; border-radius: 4px; }
        .bloc-adresses .bloc h3 { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2c7a4b; color: white; padding: 8px 12px; text-align: left; }
        td { padding: 8px 12px; border-bottom: 1px solid #eee; }

        .total { text-align: right; font-size: 15px; }
        .total strong { font-size: 18px; color: #2c7a4b; }

        .statut-paiement { margin-top: 20px; padding: 10px 16px; border-radius: 4px; font-weight: bold; }
        .statut-paiement.paye { background: #d4edda; color: #155724; }
        .statut-paiement.attente { background: #fff3cd; color: #856404; }

        .pied { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 11px; color: #999; text-align: center; }

        /* On cache le bouton imprimer quand on imprime vraiment */
        @media print {
            .btn-imprimer { display: none !important; }
            body { padding: 10px; }
        }

        .btn-imprimer {
            position: fixed; top: 20px; right: 20px;
            background: #2c7a4b; color: white; border: none;
            padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px;
        }
    </style>
</head>
<body>

<button class="btn-imprimer" onclick="window.print()">⬇ Télécharger / Imprimer</button>

<div class="entete">
    <div>
        <h1>FACTURE</h1>
        <div style="color:#555; margin-top:4px;">N° <?= htmlspecialchars($numeroFacture) ?></div>
        <div style="color:#555;">Émise le <?= htmlspecialchars(date('d/m/Y', strtotime($facture['generated_at']))) ?></div>
    </div>
    <div class="infos-societe">
        <strong>Silver Happy</strong><br>
        123 Rue de la Solidarité<br>
        69001 Lyon, France<br>
        contact@silverhappy.fr
    </div>
</div>

<div class="bloc-adresses">
    <div class="bloc">
        <h3>Émetteur (prestataire)</h3>
        <strong><?= htmlspecialchars($facture['first_name'] . ' ' . $facture['last_name']) ?></strong><br>
        <?= htmlspecialchars($facture['company_name'] ?? '') ?><br>
        SIRET : <?= htmlspecialchars($facture['siret_number'] ?? 'N/A') ?><br>
        <?= htmlspecialchars($facture['email']) ?><br>
        IBAN : <?= htmlspecialchars($facture['iban'] ?? 'Non renseigné') ?>
    </div>
    <div class="bloc">
        <h3>Destinataire</h3>
        <strong>Silver Happy SAS</strong><br>
        123 Rue de la Solidarité<br>
        69001 Lyon, France<br>
        contact@silverhappy.fr
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Période</th>
            <th style="text-align:right">Montant HT</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Rémunération des prestations réalisées</td>
            <td><?= htmlspecialchars($facture['month_label']) ?></td>
            <td style="text-align:right"><?= number_format((float)$facture['amount'], 2) ?> €</td>
        </tr>
    </tbody>
</table>

<div class="total">
    <div>Sous-total HT : <?= number_format((float)$facture['amount'], 2) ?> €</div>
    <div>TVA (0% — auto-entrepreneur) : 0,00 €</div>
    <div style="margin-top:8px"><strong>Total TTC : <?= number_format((float)$facture['amount'], 2) ?> €</strong></div>
</div>

<div class="statut-paiement <?= ($facture['payment_status'] ?? '') === 'Paye' ? 'paye' : 'attente' ?>">
    <?php if (($facture['payment_status'] ?? '') === 'Paye'): ?>
        ✔ Virement effectué le <?= htmlspecialchars(date('d/m/Y', strtotime($facture['paid_at']))) ?>
    <?php else: ?>
        ⏳ Paiement en attente de virement
    <?php endif; ?>
</div>

<div class="pied">
    Document généré automatiquement par Silver Happy — <?= date('d/m/Y H:i') ?><br>
    Ce document tient lieu de facture conformément à la législation française.
</div>

<script>
    // Ouvre automatiquement la fenêtre d'impression au chargement de la page
    window.onload = function () { window.print(); };
</script>

</body>
</html>
