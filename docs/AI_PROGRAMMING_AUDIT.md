# Audit des programmations IA

Ce document sert a auditer la coherence entre l'analyse IA du profil athlete, les performances declarees et la programmation generee.
Il ne remplace pas les tests automatises: il sert a valider la qualite produit des sorties IA avec des profils contrastes.

## Objectif

Verifier que la programmation personnalisee utilise vraiment les signaux forts du profil:

- resultats de competition;
- analyse IA du profil;
- performances renseignees par l'utilisateur;
- contraintes de volume, duree de seance, anciennete, age, blessures et objectif competition.

Le cas prioritaire est une faiblesse confirmee par deux sources. Exemple: mauvais resultats en gym suspendue en competition et faibles performances declarees sur pull-ups, toes-to-bar, chest-to-bar ou muscle-ups. Dans ce cas, la programmation doit integrer un vrai axe de progression, pas un simple rappel ponctuel.

## Profils Tests

### A. Faiblesse confirmee en gym suspendue

Signaux:

- analyse IA: limite principale sur tirage gym, toes-to-bar ou muscle-ups;
- competitions: rangs faibles sur WODs avec pull-ups, chest-to-bar, toes-to-bar, bar/ring muscle-ups;
- performances: strict pull-ups bas, toes-to-bar faible ou absent, muscle-up non acquis;
- objectif: competition locale ou Open;
- contraintes: 4 a 5 seances par semaine, 60 a 75 minutes.

Attendus:

- 2 a 3 expositions gym suspendue par semaine, avec volume recuperable;
- progression claire: strict strength, positions, kip, volume, puis integration sous fatigue;
- metcons qui reintegrent progressivement la famille gym sans saturer chaque seance;
- pas uniquement des ergos ou du renforcement general.

### B. Faiblesse visible uniquement en competition

Signaux:

- analyse IA: signale une faiblesse sur une famille issue des resultats;
- performances declarees: incompletes ou neutres sur cette famille;
- objectif: ameliorer les classements Open/qualifiers.

Attendus:

- la programmation mentionne la limite de donnees;
- elle propose une progression prudente sur la faiblesse detectee;
- elle demande implicitement ou explicitement des reperes de performance manquants dans les notes.

### C. Faiblesse visible uniquement dans les performances declarees

Signaux:

- competitions: peu de donnees ou resultats equilibres;
- performances: faiblesse nette sur une qualite, par exemple squat faible, engine bas, strict pull-up faible.

Attendus:

- la programmation cible la faiblesse declaree sans surinterpreter les competitions;
- le volume correctif est compatible avec le nombre de seances;
- le reste du cycle conserve metcon, engine, force et skill.

### D. Profil equilibre

Signaux:

- pas de faiblesse majeure dans l'analyse IA;
- performances coherentes et homogenes;
- objectif: progresser generalement.

Attendus:

- cycle equilibre;
- pas de focus artificiel sur un mouvement avance;
- progression par phases avec semaine plus legere si cycle long.

### E. Competiteur avec skills avances

Signaux:

- trains_for_competition: true;
- competition_level: rx ou elite;
- skills acquis ou presque acquis;
- objectif: Open, qualifiers ou competition locale.

Attendus:

- skills competition progressifs: handstand walk, HSPU, muscle-ups, pull-overs, selon niveau;
- densite et complexite augmentees au fil du cycle;
- metcons avec standards plus exigeants quand pertinent;
- pas d'empilement brutal de skills dans toutes les seances.

### F. Non competiteur

Signaux:

- trains_for_competition: false;
- objectif: forme generale, perte de poids, plaisir ou regularite;
- contraintes possibles: age, blessures, peu de seances.

Attendus:

- pas de domination des skills competition;
- progression lisible et durable;
- metcons et engine adaptes;
- adaptations claires en cas de limitation.

## Grille De Controle

Chaque programmation doit etre notee sur 0, 1 ou 2 pour chaque critere.

- 0: absent ou incoherent;
- 1: present mais insuffisant;
- 2: clair, exploitable et coherent.

Criteres:

- exploitation de l'analyse IA source;
- exploitation des performances declarees;
- coherence entre faiblesse detectee et contenu du cycle;
- progression concrete sur les seances du jour;
- presence de metcons dans la semaine quand pertinent;
- equilibre force, engine, haltero, gym, renfo et recuperation;
- volume correctif realiste;
- charges, pourcentages, reps et repos exploitables;
- lisibilite mobile des seances;
- adaptation selon age, anciennete, blessures, sexe et objectif competition;
- difference pertinente entre profil competiteur et non competiteur.

Score attendu:

- 18/22 ou plus: acceptable;
- 14 a 17: exploitable mais corrections souhaitables;
- moins de 14: ouvrir des issues de correction avant mise en avant produit.

## Protocole

1. Creer ou selectionner un compte test pour chaque profil.
2. Renseigner les performances correspondantes.
3. Lier les profils athletes necessaires quand le scenario utilise les resultats de competition.
4. Generer une analyse IA.
5. Generer une programmation personnalisee.
6. Valider la programmation et generer les seances detaillees.
7. Remplir la grille de controle.
8. Comparer les ecarts et ouvrir des issues ciblees.

Pour eviter de consommer trop de tokens, commencer par 2 profils:

- A. Faiblesse confirmee en gym suspendue;
- F. Non competiteur.

Lancer ensuite les autres profils quand la premiere passe est acceptable.

## Journal D'Audit

| Date | Profil | Analyse id | Programmation id | Details id | Score | Verdict | Issues creees |
| --- | --- | --- | --- | --- | ---: | --- | --- |
| A remplir | A | - | - | - | - | - | - |
| A remplir | F | - | - | - | - | - | - |

## Ecarts A Transformer En Issues

Ouvrir une issue separee si un ecart est reproductible sur au moins un profil test important.

Exemples:

- la faiblesse principale est mentionnee mais absente des seances;
- la programmation contient uniquement engine/renfo et aucun metcon;
- les temps de repos sont absents ou noyes dans des notes;
- le volume gym est trop haut pour un athlete faible;
- les skills competition dominent un profil non competiteur;
- les charges ou calories ne tiennent pas compte du sexe ou du niveau;
- la seance du jour est trop verbeuse pour mobile.
