# 🎯 GUIDE D'UTILISATION - Plateforme Quiz Sahty

**Pour**: Administrateurs & Utilisateurs Finaux  
**Dernière mise à jour**: Février 2025

---

## 📚 TABLE DES MATIÈRES

1. [Guide Admin](#-guide-admin)
2. [Guide Utilisateur](#-guide-utilisateur)
3. [FAQ](#-faq)
4. [Troubleshooting](#-troubleshooting)

---

## 👨‍💼 GUIDE ADMIN

### 1. Accédez à l'Administration

**URL**: `/quiz/admin`

Vous verrez une liste de tous les quizzes avec:
- Nom du quiz
- Description courte
- Nombre de questions
- Nombre de recommandations
- Boutons d'actions (Modifier, Supprimer)

### 2. Créer un Nouveau Quiz

1. Cliquez sur **"+ Nouveau Quiz"** (bouton vert)
2. Remplissez les champs:
   - **Nom**: Ex: "Evaluation du Stress"
   - **Description**: Expliquez brièvement l'objectif

3. **Ajouter des Questions**:
   - Cliquez **"Ajouter une question"**
   - Remplissez pour chaque question:
     - **Texte**: La question à poser
     - **Type de réponse**: 
       - Likert 0-4 (conseillé)
       - Likert 1-5
       - Oui/Non
     - **Catégorie**: stress, anxiété, concentration, sommeil, humeur
     - **Ordre**: N° d'affichage (1, 2, 3...)
     - **Question inversée**: Cochez si le scoring est inversé (0 = 4)

4. Cliquez **"Créer"** ou **"Modifier"**

### 3. Gérer les Recommandations

#### Accédez à la liste
URL: `/recommandation`

#### Créer une Recommandation
1. Cliquez **"+ Nouvelle Recommandation"**
2. Remplissez:
   - **Nom**: Référence interne
   - **Titre court**: Ce qui s'affiche à l'utilisateur
   - **Description**: Explication détaillée
   - **Conseils**: Énumérez (un par ligne, commençant par •)
   - **Score min/max**: Seuil de déclenchement
   - **Quiz associé**: Sélectionnez le quiz
   - **Catégories cibles**: `stress,concentration` (séparé par virgule)
   - **Sévérité**: Low / Medium / High

### 4. Recherche & Tri Avancés

#### Pour Quizzes:
- **Recherche**: Entrez du texte dans "Nom ou description"
- **Trier par**:
  - Date création (récent)
  - Nom (A-Z)
  - Nombre de questions
- **Ordre**: Descendant (récent d'abord) ou Ascendant
- Cliquez **"Filtrer"**

#### Pour Réinitialiser:
- Cliquez **"Réinitialiser"** ou videz les champs

### 5. Supprimer un Quiz/Recommandation

1. Cliquez le bouton **Corbeille** (rouge)
2. Confirmez la suppression
3. ⚠️ **ATTENTION**: C'est irréversible!

---

## 👥 GUIDE UTILISATEUR

### 1. Consulter les Quizzes Disponibles

**URL**: `/quiz`

Vous verrez une liste de quizzes avec:
- Titre du quiz
- Description courte
- Nombre de questions
- Bouton **"Commencer"** (blue)

### 2. Remplir un Quiz

1. Cliquez **"Commencer"** sur le quiz de votre choix
2. Lisez chaque question et sélectionnez votre réponse:
   - Pour **Likert 0-4**: Utilisez l'échelle (0 = jamais, 4 = très souvent)
   - Pour **Oui/Non**: Cochez la case correspondante
3. Une fois toutes les questions remplies, cliquez **"Valider mes réponses"**

### 3. Consulter vos Résultats

Après la soumission, vous verrez:

#### Score Global
- Un grand chiffre avec votre score total
- **Couleur**:
  - 🟢 Vert = Score faible (tout va bien)
  - 🟠 Orange = Modéré (quelques ajustements)
  - 🔴 Rouge = Élevé (consultez un professionnel)

#### Graphique Radar
- Visualize votre score par **catégorie**
- Identifiez vos domaines faibles

#### Recommandations Personnalisées
- **Basées sur votre score et catégories**
- Triées par **importance**:
  - 🔴 **Élevé**: À traiter prioritairement
  - 🟠 **Moyen**: Important mais pas urgent
  - 🟢 **Faible**: Méthodes simples

#### Pour chaque Recommandation:
- **Titre**: Le titre d'une ligne
- **Description**: Pourquoi c'est important
- **Conseils**: Actions concrètes à prendre

### 4. Refaire le Quiz

Après les résultats, cliquez:
- **"Refaire le quiz"** pour recommencer
- **"Retour aux quizzes"** pour choisir un autre

---

## ❓ FAQ

### Q: Combien de temps prend un quiz?
**A**: En moyenne 5-10 minutes selon le type.

### Q: Mes réponses sont-elles sauvegardées?
**A**: Actuellement non (fonctionnalité future envisagée).

### Q: Puis-je modifier mes réponses?
**A**: Vous devez recommencer le quiz entièrement.

### Q: Les recommandations sont-elles des avis médicaux?
**A**: Non, ce sont des suggestionsd'hygiène de vie. Consultez un professionnel pour des diagnostics.

### Q: Quelles sont les catégories disponibles?
**A**:
- 🧠 **Stress**: Gestion du stress
- 😰 **Anxiété**: Troubles anxieux
- 🎯 **Concentration**: Attention/Focus
- 😴 **Sommeil**: Qualité du repos
- 😊 **Humeur**: Bien-être émotionnel

### Q: Je ne vois aucun quiz?
**A**: L'administrateur doit en créer. Contactez-le.

### Q: Comment fonctionne le "scoring inversé"?
**A**: Pour certaines questions (ex:"Je dors bien"), la réponse "4" = faible problème, donc le score est inversé.

---

## 🔧 TROUBLESHOOTING

### Problème: Je vois une erreur 404
**Solution**:
- Vérifiez l'URL
- Assurez-vous que le serveur est allumé
- Rechargez la page

### Problème: Le formulaire d'upload de quiz ne charge pas
**Solution**:
- Videz le cache du navigateur (Ctrl+F5)
- Essayez dans un onglet privé (Incognito)
- Essayez un autre navigateur

### Problème: Les questions ne s'affichent pas dans le formulaire
**Solution**:
- Assurez-vous qu'au moins 1 question est ajoutée
- Vérifiez que les questions ne sont pas supprimées accidentellement

### Problème: Le graphique Radar ne s'affiche pas
**Solution**:
- JavaScript doit être activé
- Vérifiez la console (F12 > Console) pour les erreurs
- Essayez un navigateur moderne (Chrome, Firefox, Edge)

### Problème: Les recommandations ne correspondent pas à mon score
**Solution**:
Ceci peut être normal si:
- Votre score ne correspond à aucune plage définie
- Aucune recommandation n'a votre catégorie problématique
- Contactez l'administrateur pour vérifier la configuration

### Problème: Je ne peux pas supprimer un quiz
**Solution**:
- Assurez-vous que vous êtes admin
- Rechargez la page et réessayez
- Vérifiez la console pour les erreurs

---

## 📞 SUPPORT

Pour toute question ou problème:
1. Consultez cette documentation
2. Essayez les solutions de troubleshooting
3. Contactez l'administrateur du site

---

## 📋 CHECKLISTE AVANT DE COMMENCER

- [ ] J'ai accès à `/quiz` (page d'accueil des quizzes)
- [ ] Je vois au moins 1 quiz disponible
- [ ] Mon navigateur supporte JavaScript
- [ ] Je comprends l'échelle de réponses utilisée

---

**Bonne utilisation! 🎉 Vos réponses nous aident à mieux comprendre votre bien-être.**

*Cette plateforme est conçue pour vous orienter, pas pour remplacer consultes professionnelles.*
