# SymconBotvac

SymconBotvac ist eine Erweiterung für die Heimautomatisierung IP Symcon. Mithilfe dieser Erweiterung könnt Ihr euren Vorwerk Kobold VR200 steuern. Später sollen auch weitere Botvac-Roboter dazu kommen.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

- Automatische Anlage mithilfe der Vorwerk Zugangdaten.
- Steuerung des Staubsaugerroboter

### 2. Voraussetzungen

- IP-Symcon ab Version 4.4 (ggf. auch früher)

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/traxanos/SymconBotvac.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Anlage eines Ordner Namens Botvac
- Unter "I/O Instanzen" eine Instanz "BotvacControl" anlegen.
- Eingabe der Zugangsdaten
- Sowie die zuvor angelegte Kategorie
- Starte "Gerätea"

__Konfigurationsseite__:

Name                   | Beschreibung
---------------------- | ---------------------------------
E-Mail                 | E-Mailadresse aus der Vorwerkapp
Passwort               | Passwort aus der Vorwerkapp
Button "Geräteabgleich"| Abgleich aller Roboter

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name         | Typ       | Beschreibung
------------ | --------- | ----------------
Zustand      | Integer   | Gibt den Zustand des Roboter an.
Aktion       | Integer   | Gibt die Aktion an die der Roboter gerade ausführt. (Wird ausgeblendet bei Inaktivität)
Fehler       | String    | Gibt einen Fehler aus. (Wird ausgeblendet solange es keine Fehler/Meldungen gibt.)
Kommando     | Integer   | Steuert die Aktion die er Roboter ausführen soll.
Eco Modus    | Boolean   | Gibt an ob die nächste Reinigung im Eco Modus laufen soll.
Zeitplan     | Boolean   | De-/Aktiviert den Zeitplan.
Im Dock      | Boolean   | Ist der Roboter in der Basisstation.
Batterie     | Integer   | Gibt den Zustand der Batterie an. Der Name bekomme eine Suffix " (lädt)" angehangen während des Ladevorgang.
Model        | String    | Modelname
Firmware     | String    | Firmwareversion

##### Profile:

Name                | Typ       | Beschreibung
------------------- | --------- | ----------------
Botvac.Action       | Integer   |
Botvac.Command.%ID% | Integer   | Ein Profil pro Roboter. Wird beim Abgleich angepasst, so dass nur verfügbare Kommandos enthalten sind
Botvac.YesNo        | Boolean   |

### 6. WebFront

Über das WebFront kann die der Roboter gesteuert werden.

### 7. PHP-Befehlsreferenz

`BVC_Start(integer $InstanzID, boolean $Spot);`
Startet die Reinigung mit der InstanzID $InstanzID.
Mittels $Spot = true, wird nur eine Spotreinigung durchgeführt.
Die Funktion liefert keinerlei Rückgabewert.
`BVC_Start(12345, false);`

`BVC_Stop(integer $InstanzID);`
Stoppt die Reinigung mit der InstanzID $InstanzID.
Die Funktion liefert keinerlei Rückgabewert.
`BVC_Stop(12345);`

`BVC_Pause(integer $InstanzID);`
Pausiert die Reinigung mit der InstanzID $InstanzID.
Die Funktion liefert keinerlei Rückgabewert.
`BVC_Pause(12345);`

`BVC_Resume(integer $InstanzID);`
Wiederaufnahme der Reinigung mit der InstanzID $InstanzID.
Die Funktion liefert keinerlei Rückgabewert.
`BVC_Resume(12345);`

`BVC_SendToBase(integer $InstanzID);`
Schickt den Roboter zur Basis mit der InstanzID $InstanzID.
Beachtet dass dies erst geht sobald der Roboter die Basis erkannt hat.
Die Funktion liefert keinerlei Rückgabewert.
`BVC_SendToBase(12345);`

`SetSchedule(integer $InstanzID, boolean $Value);`
Schaltet den Zeitplan mit der InstanzID $InstanzID auf den Wert $Value (true = An; false = Aus).  
Die Funktion liefert keinerlei Rückgabewert.  
`SetSchedule(12345, false);`
