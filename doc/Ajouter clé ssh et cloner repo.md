# 🔐 Ajouter une clé SSH GitLab

---

## 1️⃣ Vérifier que l’agent SSH est actif

Ouvre **PowerShell** (ou Terminal Windows) :

```powershell
Get-Service ssh-agent
```

S’il est arrêté :

```powershell
Start-Service ssh-agent
Set-Service ssh-agent -StartupType Automatic
```

---

## 2️⃣ Créer le dossier `.ssh`

```powershell
cd ~
mkdir .ssh
cd .ssh
```

Cela crée :

```
C:\Users\<TON_NOM>\.ssh
```

---

## 3️⃣ Générer une clé SSH

Commande recommandée :

```powershell
ssh-keygen -t ed25519 -C "gitlab"
```

Quand il demande :

```
Enter file in which to save the key
```

→ Appuie sur **Entrée**

Ça crée :

```
id_ed25519
id_ed25519.pub
```

Pour la passphrase :

* optionnel (sécurité en plus)
* ou Entrée pour aucune

---

## 4️⃣ Charger la clé dans l’agent SSH

```powershell
ssh-add $env:USERPROFILE\.ssh\id_ed25519
```

Vérifier :

```powershell
ssh-add -l
```

---

## 5️⃣ Copier la clé publique

```powershell
type $env:USERPROFILE\.ssh\id_ed25519.pub
```

Copie toute la ligne qui commence par :

```
ssh-ed25519 AAAAC3...
```

---

## 6️⃣ Ajouter la clé dans GitLab

Dans GitLab :

1. Clique sur ton avatar
2. **Preferences**
3. **SSH Keys**
4. Colle la clé
5. Titre : laisser le titre par défaut ou le changer si vous êtes un thug
6. **Add key**

---

## 7️⃣ Cloner le repo

Dans VSCode,

Soit dans le terminal :

```powershell
git clone git@gitlab.com:floriangarciasoto/adopte-un-boss.git
```

Ou via l'extension VSCode en cliquant sur la page du projet sur GitLab.

Et ça devrait marcher 🤞