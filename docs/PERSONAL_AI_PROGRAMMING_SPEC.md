# Cahier Des Charges - Programmation Personnelle IA

Ce document decrit la direction produit de la programmation personnelle IA.
Il complete `docs/AI_PROGRAMMING_AUDIT.md`, qui sert a verifier la qualite des programmations generees.

## Probleme A Resoudre

Une demande de programmation trop vague pousse l'IA a produire une programmation plausible mais generique.
MonWOD doit eviter une programmation qui s'adresse a tout le monde et donc a personne.

La programmation doit partir:

- d'une famille de programmation explicite;
- de l'analyse IA du profil athlete;
- des resultats de competition importes;
- des performances declarees;
- des contraintes personnelles: age, sexe, anciennete, blessures, disponibilite, duree de seance, objectif competition.

## Regle Centrale

La programmation doit etre specifique a cet athlete.
Ne pas proposer une programmation generique.
Chaque choix important doit decouler d'un point fort, d'une faiblesse, d'une contrainte ou d'un objectif explicite.

## Familles De Programmation

La demande de programmation personnelle doit proposer une famille obligatoire.
Cette famille pilote le prompt, les contraintes, les blocs attendus et les controles qualite.

### CrossFit General

Objectif: proposer une programmation principale, comme si l'athlete ne suivait que cette programmation.

Attendus:

- couvrir force, renforcement, gymnastics, halterophilie, metcons, engine et skills;
- tenir compte des points forts/faibles de l'athlete sans abandonner la diversite CrossFit;
- proposer des metcons dans la semaine;
- adapter les mouvements, volumes, charges, calories et standards selon les donnees athlete;
- preparer l'athlete a ne pas etre desempare en competition.

Le niveau Intermediaire / RX / Elite est surtout pertinent ici.
Il peut influencer:

- le choix des mouvements;
- les standards de competition;
- les charges;
- les volumes;
- les calories;
- la complexite technique;
- l'exposition aux skills sous fatigue.

### Faiblesses Prioritaires

Objectif: programmation complementaire a un entrainement CrossFit existant.

Attendus:

- cibler 1 ou 2 axes principaux, avec eventuellement 1 axe secondaire;
- ne pas faire "un peu de tout";
- justifier les priorites avec l'analyse IA, les competitions ou les performances declarees;
- respecter la recuperation car l'utilisateur s'entraine deja a cote;
- proposer une progression claire sur les faiblesses identifiees.

Exemples:

- gym suspendue faible;
- engine insuffisant;
- force relative basse;
- wall balls, handstand push-ups ou toes-to-bar limitants en competition.

### Renforcement Musculaire

Objectif: developper la force, l'hypertrophie, la prevention ou le renforcement specifique.

Attendus:

- pas de metcons obligatoires;
- series, repetitions, charges, RPE ou pourcentages et temps de repos explicites;
- progression coherente sur le cycle;
- deload ou semaine plus legere si le cycle le justifie;
- adaptations selon age, blessures, niveau de force et anciennete.

Cette famille peut servir un objectif CrossFit, mais elle ne doit pas se transformer en programmation CrossFit generale.

### Gymnastics

Objectif: developper les capacites gymniques utiles au CrossFit.

Attendus:

- distinguer apprentissage, consolidation, volume et expression sous fatigue;
- adapter selon la maitrise reelle des mouvements;
- privilegier force stricte, positions, controle, volume recuperable et progression;
- ne pas mettre sous fatigue un mouvement non maitrise;
- integrer les skills competition seulement si le niveau et l'objectif le justifient.

Mouvements possibles:

- strict pull-ups;
- chest-to-bar;
- toes-to-bar;
- bar muscle-ups;
- ring muscle-ups;
- handstand push-ups;
- handstand walk;
- pistols;
- dips et supports anneaux.

### Halterophilie

Objectif: progresser techniquement et physiquement sur les mouvements d'halterophilie.

Attendus:

- exploiter les max declares quand ils existent;
- preciser charges, pourcentages, RPE, reps et repos;
- inclure selon le besoin complexes, pulls, squats, positions, vitesse et stabilite;
- organiser une progression sur le cycle;
- distinguer snatch, clean, jerk et variantes selon l'objectif.

Cette famille peut contenir du conditioning leger ou accessoire, mais l'objectif principal reste haltero.

### Engine / Cardio

Objectif: developper la capacite aerobie, le seuil, la repeatability ou le pacing.

Cette famille inclut pour l'instant le monostructurel.
On pourra separer "monostructurel" plus tard si le produit en a besoin.

Attendus:

- utiliser ergs, run, bike, row, ski, intervals, zone 2, seuil ou efforts longs;
- adapter aux donnees disponibles: calories/minute, allures, benchmarks, volume tolerable;
- preciser intensite, duree, repos, objectif de pacing et consignes de progression;
- ne pas masquer une faiblesse de force ou gym sous une programmation cardio generique.

### Hyrox

Objectif: developper une capacite hybride specifique Hyrox.

Attendus:

- travailler run, transitions, ergs, sled, lunges, wall balls, farmer carry, burpees broad jumps et pacing;
- distinguer un vrai Hyrox complet d'un entrainement Hyrox;
- programmer la tolerance a l'effort long et la fatigue musculaire locale;
- adapter le volume a l'experience, au niveau et a la recuperation de l'athlete.

Hyrox ne doit pas etre confondu avec CrossFit general.

## Niveau Intermediaire / RX / Elite

Le niveau Intermediaire / RX / Elite ne doit pas etre le pilote principal de toutes les familles.

Il est principalement utile pour:

- CrossFit general;
- certains metcons ou skills competition;
- calibrer les standards de competition.

Pour les autres familles, le niveau doit rester secondaire.
La programmation doit d'abord utiliser les donnees reelles de l'athlete.

## Donnees A Fournir Au Prompt

Le prompt doit recevoir autant que possible:

- famille de programmation;
- objectif libre de l'utilisateur;
- nombre de semaines;
- nombre de seances par semaine;
- duree cible par seance;
- age;
- sexe;
- anciennete CrossFit;
- blessures ou limitations;
- objectif competition;
- niveau competition si pertinent;
- analyse IA recente;
- forces et faiblesses detectees;
- performances declarees;
- profils athletes lies;
- resultats de competitions pertinents;
- contraintes de materiel si disponibles.

## Contraintes De Generation

Chaque programmation doit:

- expliquer l'intention generale du cycle;
- presenter une structure hebdomadaire comprehensible;
- produire des seances exploitables sur mobile;
- preciser series, repetitions, charges ou intensites et temps de repos;
- eviter les retours au calme verbeux inutiles dans les seances du jour;
- proposer plus de travail utile que pas assez, sans depasser les limites de duree et volume;
- respecter les bornes produit: 4 a 8 semaines, 1 a 6 seances par semaine, 30 a 180 minutes par seance.

## Controle Qualite Par Famille

### CrossFit General

La programmation est invalide si elle ne contient que du renforcement et de l'engine.
Elle doit exposer l'athlete a une variete CrossFit reelle, tout en restant adaptee a son profil.

### Faiblesses Prioritaires

La programmation est invalide si elle se disperse sur trop d'axes ou ignore la faiblesse principale.

### Renforcement Musculaire

La programmation est invalide si elle impose des metcons hebdomadaires sans raison.

### Gymnastics

La programmation est invalide si elle met regulierement sous fatigue un skill non maitrise.

### Halterophilie

La programmation est invalide si elle ne donne pas de charges, intensites ou criteres de qualite technique exploitables.

### Engine / Cardio

La programmation est invalide si elle ne precise pas les zones, intensites, repos ou objectifs de pacing.

### Hyrox

La programmation est invalide si elle ne contient pas de travail run + stations ou si elle confond systematiquement entrainement Hyrox et simulation complete.

## Issues Liees

- GitHub issue #310: Roadmap: familles de programmation personnelle IA.
- `docs/AI_PROGRAMMING_AUDIT.md`: protocole d'audit des sorties IA.
