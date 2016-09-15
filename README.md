# Developper toolkit

## Introduction

Ce programme est un développer toolkit pour Dynacase Platform, il facilite 
l'initialisation et la maintenance d'un projet Dynacase Platform.

La documentation est générée à l'[adresse suivante][doc]

## Contenu

Il contient, les fonctionnalités suivantes :

* templates :
    * application : initialisation d'une application,
    * action : initialisation d'une action (enregistrement, droit, layout, code),
    * familles :
       * structure : fichier de structure d'une famille,
       * paramétrage : fichier de paramétrage d'une famille,
       * classe : fichier de code source d'une famille,
    * paquet : structure d'un paquet
* i18n :
    * po : extraction initialisation/mise à jour des po
* package :
    * production du paquet (format webinst)
* stubs :
    * génération des stubs

## Dépendances

Les dépendances du projet sont décrites dans le fichier composer.json et s'installe à l'aide de [composer](https://getcomposer.org/)

`composer install`

## Packaging

Le packaging du code dans un phar est réalisé à l'aide du projet [box](https://github.com/box-project/box2) qui facilite
la réalisation d'un phar exécutable.
Pour repackager le projet après une modification des sources, il faut une fois dans le répertoire courant des sources
faire :

`./box.phar build`

NB : il faut au préalable avoir installer les dépendances.

## Window packaging

Il est possible de générer un zip pour une exécution autonome sous window en utilisant la commande 

`make devtool-win32.zip`

L'ensemble des dépendances (composer et autres) est alors téléchargé et un fichier `devtool-win32.zip` est produit
celui-ci contient alors le nécessaire pour lancer les devtools sous window (testé sous window 7 32 et 64 bit).

Vous pouvez ensuite nettoyer avec la commande :

`make clean`

[doc]: http://docs.anakeen.com/dynacase/3.2/devtools/index.html