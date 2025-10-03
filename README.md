# Gestion de Stocks (Symfony 7)

Une application web simple pour gérer des magasins, des matériels, et leurs affectations, avec traçabilité (audit) et historique des mouvements de stock.

<table>
  <tr>
    <td><img src="Capture d’écran 2025-10-03 à 15.37.06.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.37.06.png"></td>
    <td><img src="Capture d’écran 2025-10-03 à 15.37.15.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.37.15.png"></td>
  </tr>
  <tr>
    <td><img src="Capture d’écran 2025-10-03 à 15.37.24.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.37.24.png"></td>
    <td><img src="Capture d’écran 2025-10-03 à 15.37.49.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.37.49.png"></td>
  </tr>
  <tr>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.08.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.08.png"></td>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.18.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.18.png"></td>
  </tr>
  <tr>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.26.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.26.png"></td>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.41.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.41.png"></td>
  </tr>
  <tr>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.54.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.54.png"></td>
    <td><img src="Capture d’écran 2025-10-03 à 15.38.59.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.38.59.png"></td>
  </tr>
  <tr>
    <td colspan="2"><img src="Capture d’écran 2025-10-03 à 15.39.09.png" width="1920" alt="Capture d’écran 2025-10-03 à 15.39.09.png"></td>
  </tr>
</table>

## Fonctionnalités

- Gestion des magasins: création, modification, suppression, affichage des métadonnées (créé par, dernière modification) et audit.
- Gestion des matériels: listing filtré, détail avec mouvements (ajout/retour/ajustement), ajustement de stock, audit create/update/delete.
- Affectations matériel ↔ magasin: création, modification, suppression, retour de stock, audit des actions.
- Dashboard: indicateurs clés (magasins, matériels, affectations actives, stock total, ruptures, faible stock, mouvements récents, catégories).
- Authentification: page de connexion et protection CSRF.
 - Badges d’état (UI):
   - Magasins: « Actif » / « Archivé » + compteur d’affectations.
   - Matériels: disponibilité synthétique (En stock / Assigné / Indisponible).
   - Affectations: « Actif » / « Partiel » / « Retourné » / « Archivé ».
 - Confirmations unifiées: suppression/archivage/restauration avec modale SweetAlert via `data-confirm`.
 - Formulaire d’affectation dynamique: le `max` de quantité se met à jour selon le stock du matériel sélectionné.
 - Export PDF des affectations magasin: logo, pagination « Page X / Y », date/heure et zones de signature.

## Stack technique

- PHP `>= 8.2`
- Symfony `7.3.*` (Framework, Security, Twig, Forms, etc.)
- Doctrine ORM `^3.5` et Migrations
- Twig pour les templates
- PostgreSQL (via Docker Compose fourni) ou toute base supportée par Doctrine

## Démarrage rapide

1. Installer les dépendances:
   - `composer install`

2. Base de données:
   - Option A (Docker):
     - `docker compose up -d` (utilise `compose.yaml` et expose PostgreSQL en local)
     - Configurer `DATABASE_URL` dans `.env` ou `.env.local` (ex: `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8`)
   - Option B (PostgreSQL local / autre SGBD):
     - Configurer `DATABASE_URL` en conséquence.

3. Initialiser la base:
   - `php bin/console doctrine:database:create`
   - `php bin/console doctrine:migrations:migrate -n`
   - (Optionnel) Charger des jeux de données si des fixtures sont disponibles: `php bin/console doctrine:fixtures:load -n`

4. Lancer le serveur de développement:
   - Sans Symfony CLI: `php -S 127.0.0.1:8001 -t public`
     - Si le port 8001 est pris, utilisez `8002` ou un autre port libre.
   - Avec Symfony CLI: `symfony server:start --no-tls`

5. Ouvrir l’application:
   - `http://127.0.0.1:8001/` (ou l’URL fournie par Symfony CLI)

## Comptes et sécurité

- Créez un utilisateur via un formulaire prévu ou via un script/fixtures selon votre configuration.
- La page de connexion est accessible via `Menu → Se connecter`.
- Le token CSRF est utilisé pour les actions sensibles (authentification, suppression, ajustements, etc.).

## Traces et audit

- Les contrôleurs journalisent les actions sur les entités (create/update/delete, return, adjust) dans l’entité `Audit`.
- Les pages de détail (magasin et matériel) affichent:
  - Métadonnées: qui a créé / modifié et quand.
  - Historique des actions: liste chronologique des audits.
- Les mouvements de stock (`Movement`) capturent les opérations d’ajout, retour, ajustement, et l’utilisateur qui a déclenché l’action si présent.

## Confirmations d’actions (SweetAlert)

- Les formulaires de suppression/archivage/restauration utilisent des attributs `data-confirm` et `data-confirm-type`.
- Le handler global (SweetAlert) est défini dans `templates/base.html.twig`.
- Les prompts natifs `window.confirm` ont été retirés des assets pour éviter les doublons.

## PDF des affectations magasin

- Route: `GET /store/{id}/assigned.pdf` (lien « Exporter PDF » sur la page Détail magasin).
- Contenu: logo, titre, table des affectations actives, date/heure de génération, pagination en pied de page, zones de signature.
- Logo: fichier local `public/img/aldi-logo.png` (modifiable). Pour utiliser un autre logo, remplacez ce fichier ou ajustez la balise `<img>` dans `templates/store/assigned_pdf.html.twig`.
- Dompdf: `isRemoteEnabled=false` (ressources externes désactivées). Privilégier CSS simple et polices supportées (DejaVu Sans).

## Structure du code (repères)

- `src/Entity/` : entités Doctrine (User, Store, Equipment, Assignment, Movement, Audit, Category), documentées pour débutants.
- `src/Controller/` : contrôleurs CRUD et métier (StoreController, EquipmentController, AssignmentController, DashboardController, SecurityController) avec docblocks pédagogiques.
- `templates/` : templates Twig avec commentaires d’en-tête expliquant les données attendues et l’objectif.

## Conseils d’utilisation (quantité d’affectation)

- Lors de la création/édition d’une affectation, le champ quantité est borné côté client (min=1) et le `max` s’adapte au stock du matériel sélectionné.
- Un aide visuelle indique le stock disponible. Côté serveur, les contrôleurs empêchent toute sortie de stock négative.

## Guide d’utilisation (pas à pas)

1) Se connecter
- Depuis le menu, cliquez sur `Se connecter` et connectez-vous.
- Les actions seront alors attribuées à votre compte dans les audits et mouvements.

2) Créer une catégorie (optionnel)
- Si vous organisez vos matériels par catégories, créez-en via l’interface prévue (ou via DB/fixtures).

3) Créer un matériel
- Allez dans `Matériels` → `Nouveau matériel`.
- Renseignez nom, référence, catégorie, état, et stock initial.
- À la création, un audit `create` est enregistré.

4) Ajuster le stock d’un matériel
- Dans la page de détail du matériel, section `Ajuster le stock`.
- Choisissez `Augmenter` ou `Diminuer`, entrez une quantité, validez.
- Un `Movement` de type `ajustement` est créé et, si connecté, `performedBy` est renseigné.

5) Créer un magasin
- Allez dans `Magasins` → `Nouveau magasin`.
- Renseignez nom, adresse, responsable.
- À la création, un audit `create` est enregistré.

6) Affecter un matériel à un magasin
- Allez dans `Affectations` → `Nouvelle affectation`.
- Choisissez le magasin, le matériel, et la quantité.
- Le stock du matériel est décrémenté, un `Movement` de type `ajout` est enregistré, et un audit `create` est ajouté.

7) Modifier une affectation
- Dans la page `Affectations`, ouvrez une affectation et cliquez `Modifier`.
- Si la quantité change, le stock est ajusté en conséquence et un audit `update` est ajouté.

8) Enregistrer un retour (restitution)
- Depuis la page de détail d’une affectation, utilisez le formulaire de `Retour`.
- Le stock du matériel est incrémenté, un `Movement` de type `retour` est créé, et un audit `return` est ajouté.

9) Consulter l’audit et les mouvements
- Page `Détail magasin`: section `Métadonnées` (créé/modifié par) et `Historique des actions` (audits).
- Page `Détail matériel`: table des `Mouvements` et historique `Audit` affiché si fourni par le contrôleur.

10) Suppression (avec garde-fous)
- Un matériel ne peut être supprimé s’il a des affectations liées.
- En cas de suppression autorisée, un audit `delete` est enregistré.

11) Exporter le PDF d’un magasin
- Ouvrez `Magasins` → `Voir` sur un magasin, puis cliquez sur « Exporter PDF ».
- Vérifiez que le logo et la pagination apparaissent correctement.

## Développement

- Générer du code (ex: entités): `php bin/console make:entity` (via Maker Bundle).
- Voir la configuration Doctrine: `config/packages/doctrine.yaml` et `config/packages/doctrine_migrations.yaml`.
- Logs/erreurs: surveiller le terminal et/ou le Profiler Symfony si activé (`symfony/web-profiler-bundle`).

## Tests (optionnel)

- PHPUnit est installé en dev (`require-dev`). Lancez les tests avec: `php bin/phpunit`.

## Contribuer

- Ouvrez une issue pour proposer une amélioration.
- Respectez le style du code existant et la logique métier.

## Licence

Ce projet est sous licence MIT.