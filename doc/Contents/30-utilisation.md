# Utilisation {#devtools:6e013ebc-697a-4c18-8e38-d571b94d4261}

## Liste des commandes {#devtools:639d7c38-7daf-4984-9c02-b09e9e5b0804}

Lancé sans option, le developer toolkit liste les commandes disponibles :

    ❯ ./dynacase-devtool.phar
    DevTools for Dynacase 3.2
    Devtool version : 3.2
    You can access to the sub command : 
        createAction
        createApplication
        createFamily
        createInfoXml
        createModule
        createWorkflow
        deploy
        extractPo
        generateStub
        generateWebinst
        importFamily
        poToCsv

Chaque commande est auto-documentée via l'option `-h` ou `--help`.

## le fichier `build.json` {#devtools:f0fb9907-44e1-4956-aea1-14beb5cc077c}

Lors de l'utilisation de la commande `createModule`,
une configuration du developer toolkit est initialisée dans le fichier `build.json`.

Ce fichier json contient les clés suivantes :

`moduleName`
:   Le nom du module.
    
    Il est utilisé pour nommer le webinst et pour compléter le `info.xml`
    (remplacement de `@moduleName@` lors de la génération du webinst).

`version`
:   Le numéro de version du module.
    
    Il est utilisé pour nommer le webinst et pour compléter le `info.xml`
    (remplacement de `@version@` lors de la génération du webinst).

`release`
:   Le numéro de release du module.
    
    Il est utilisé pour nommer le webinst et pour compléter le `info.xml`
    (remplacement de `@release@` lors de la génération du webinst).

`application`
:   un tableau contenant le nom logique de chacune des applications du module

`includedPath`
:   un tableau contenant le path de tous les répertoires autres que les applications à inclure dans le webinst généré

`lang`
:   La liste des langues pour lesquelles les catalogues de traduction doivent être générés

`csvParam`
:   un objet définissant les paramètres des fichiers csv du module.
    
    Il contient les clés suivantes :
    
    `enclosure`
    :   caractère de délimiteur de texte
    
    `delimiter`
    :   caractère de séparation des champs

`toolsPath`
:   un objet définissant le chemin vers les outils utilisés par le developer toolkit.
    Il est à renseigner lorsque les outils nécessaires ne sont pas accessibles depuis le PATH.
    
    Il contient les clés suivantes :
    
    `gettext`
    :   path vers le répertoire contenant le binaire `xgettext`.

<span class="flag inline nota-bene"></span> Tout répertoire qui n'est ni dans le tableau `application`,
ni dans le tableau `includedPath` n'est pas inclus dans le webinst et donc pas déployé sur le serveur.
