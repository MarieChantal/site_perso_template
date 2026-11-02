# Mettre le site en ligne sur OVH

Ce guide part d'un hébergement OVH déjà commandé : l'offre gratuite livrée avec un nom de domaine (100 Mo, PHP, sans base de données) suffit largement. Le site tourne aussi sur n'importe quel hébergement Apache avec PHP 7.4 ou plus récent.

## 1. Récupérer les accès FTP

1. Connecte-toi à l'[espace client OVH](https://www.ovh.com/manager/).
2. Va dans **Web Cloud → Hébergements**, puis choisis ton hébergement.
3. Ouvre l'onglet **FTP - SSH**. Tu y trouves :
   - le **serveur FTP**, de la forme `ftp.clusterXXX.hosting.ovh.net` ;
   - l'**identifiant FTP principal** ;
   - le dossier racine, en général `www/`.
4. Le mot de passe a été envoyé par e-mail à la création de l'hébergement. Si tu ne l'as plus, clique sur **…** à côté de l'identifiant puis **Changer le mot de passe**.

## 2a. Déploiement automatique avec GitHub (recommandé)

Le dépôt contient une action GitHub (`.github/workflows/deploiement.yml`) qui envoie le site par FTP à chaque modification de la branche `main`. Elle n'envoie que les fichiers modifiés, et laisse de côté `README.md`, `docs/` et `.github/`.

1. Dans ton dépôt GitHub, va dans **Settings → Secrets and variables → Actions**.
2. Clique sur **New repository secret** et crée :

   | Secret | Valeur |
   | --- | --- |
   | `FTP_SERVEUR` | le serveur FTP, par exemple `ftp.cluster0XX.hosting.ovh.net` |
   | `FTP_UTILISATEUR` | l'identifiant FTP |
   | `FTP_MOT_DE_PASSE` | le mot de passe FTP |
   | `FTP_DOSSIER` | facultatif : le dossier distant, `./www/` par défaut |

3. Lance un premier envoi : onglet **Actions → Déploiement sur OVH → Run workflow**, ou pousse une modification sur `main`.
4. Ouvre ton nom de domaine dans un navigateur.

Les secrets ne sont jamais visibles, ni dans le dépôt ni dans les journaux de l'action, même si le dépôt est public. Tant qu'ils n'existent pas, l'action se termine sans rien faire.

## 2b. Envoi à la main avec un client FTP

Avec [FileZilla](https://filezilla-project.org/) ou un autre client FTP :

1. Connecte-toi avec le serveur, l'identifiant et le mot de passe de l'étape 1 (port 21).
2. Ouvre le dossier `www/` sur le serveur.
3. Envoie tout le contenu du dépôt, **y compris les fichiers cachés** `.htaccess` et `inc/.htaccess` (dans FileZilla : menu **Serveur → Forcer l'affichage des fichiers cachés**).
4. Inutile d'envoyer `README.md`, `README.en.md`, `LICENSE`, `docs/` et `.github/`.

Pour une mise à jour, renvoie seulement les fichiers modifiés.

## 3. Activer HTTPS

1. Dans l'espace client : **Web Cloud → Hébergements → ton hébergement → Informations générales**, ligne **Certificat SSL** : commande le certificat Let's Encrypt gratuit s'il n'est pas déjà actif. Il faut parfois quelques heures.
2. Une fois que `https://ton-domaine` fonctionne, ouvre `.htaccess` et retire les `#` des deux lignes sous « HTTPS » :

   ```
   RewriteCond %{HTTPS} off
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. Indique l'adresse en `https://` dans `site.txt` (`adresse:`), pour le flux RSS et le plan du site.

## 4. Installer le site dans un sous-dossier

Si le site n'est pas à la racine du domaine, par exemple `https://ton-domaine.fr/atelier/` :

- mets `FTP_DOSSIER` à `./www/atelier/` (ou envoie les fichiers dans `www/atelier/`) ;
- dans `.htaccess`, corrige les lignes `ErrorDocument` : `ErrorDocument 404 /atelier/404.php` (idem pour 403) ;
- indique l'adresse complète dans `site.txt` : `adresse: https://ton-domaine.fr/atelier/`.

## En cas de problème

- **Page blanche ou erreur 500** : vérifie la version de PHP dans **Hébergements → Informations générales → Version PHP globale**. Il faut 7.4 ou plus récent.
- **Les accents s'affichent mal** : le `.htaccess` n'a pas été envoyé. Active l'affichage des fichiers cachés dans ton client FTP.
- **L'action GitHub échoue à la connexion** : vérifie le nom du serveur et le mot de passe dans les secrets ; un mot de passe changé chez OVH doit aussi être changé dans GitHub.
- **Les changements de style n'apparaissent pas** : la feuille de style est mise en cache une semaine ; un rechargement forcé (Ctrl+Maj+R) suffit.
