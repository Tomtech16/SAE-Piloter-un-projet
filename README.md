# SAE-Piloter-un-projet
Projet SAE piloter un projet informatique : un systeme de sauvegarde de donnée
# 🗄️ Console de supervision des sauvegardes (Maquette)

Maquette HTML/CSS/JS d’une **console de supervision de sauvegardes**, inspirée de l’interface **Veeam Backup & Replication**.  
Ce projet s’inscrit dans le cadre d’une **SAE – Cycle de vie logiciel (Unified Process)**.

⚠️ Il s’agit d’une **maquette fonctionnelle** (front-end uniquement), sans backend réel.

---

## 🎯 Objectifs du projet

- Illustrer l’**expression des besoins client** via une interface graphique
- Proposer une **supervision centralisée des sauvegardes**
- Simuler la gestion de :
  - plusieurs serveurs à sauvegarder
  - jobs de sauvegarde
  - états (succès / échec)
  - logs système
- Préparer une base exploitable pour une future intégration Node.js / Fastify

---

## 🖥️ Fonctionnalités présentes

### 📊 Dashboard
- Vue globale des jobs de sauvegarde
- État des serveurs (succès / échec / opérationnel)
- Boutons d’actions :
  - relancer un job
  - relancer tous les jobs
  - consulter les détails d’erreur

### 🗂️ Gestion des serveurs
- Serveur Web
- Serveur BDD
- Serveur Logs
- Serveur Test
- Serveur de sauvegarde central (stockage + supervision)

### 🧾 Logs système
- Logs dynamiques façon console
- Horodatage automatique
- Codes couleur :
  - Info
  - Warning
  - Error

### 🧭 Navigation
- Interface **multi-pages dynamique** (SPA simple en JavaScript)
- Onglets :
  - Dashboard
  - Jobs
  - Paramètres

### 🪟 Modales
- Fenêtre de détails lors d’un échec de sauvegarde

---

## 🛠️ Technologies utilisées

- **HTML5**
- **CSS3**
- **JavaScript (Vanilla)**
- Aucun framework (choix pédagogique)

### Installation
- Prérequis, il faut installer SSH sur les deux serveurs, ensuite il vous faudra installer Apache2 également sur les deux serveurs ainsi que le module PHP.
- Les scripts se situant dans /var/www/html devrons être insérer, suivez les noms des dossiers (VM1 et pour VM2)
- Le dossier /root contient l'intégralité des scripts à devoir éxecuter après l'ajout des différentes clé ssh et scripts .html et .php --> n'oubliez pas d'ajouter les droits ! (chmod 777 ./le_fichier)
- Vérifiez que le service apache2 tourne bien (systemctl status apache2)
- Connectez-vous sur la VM2 et aller sur http://adresse_ip_VM1/backup.html
- L'ensemble des jobs sont visible, vous pourrez alors relancer le job souhaité --> la console vous permettra de suivre en temps réel les opérations.
