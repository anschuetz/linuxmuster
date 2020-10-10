Konfiguration
=============
Im Skript oben muss das Bundesland konfiguriert werden, für das die Abfrage stattfindet.
Möglich sind folgende Codes:

  BW, BY, BE, BB, HB, HH, HE, MV, NI, NW, RP, SL, SN, ST, SH, TH 

Man kann auch die Häufigkeit festlegen, in der neue Daten von der API geholt werden sollen. Voreingestellt sind 604800 Sekunden (1 Woche) Das ist glaube ich häufig genug und spart Ressourcen.

"Installation"
====================
Für Systeme, die noch kein Python3 haben (z.B. Ubuntu 12.04 mit linuxmuster.net Sicherheitspatches) gibt es das Skript schultag.python2
Für aktuelle Systeme passt aber die Python3-Version, die schon unter dem Namen "schultag" im Repo liegt.
Welche Version man auch immer möchte, man kopiert sie nach /usr/bin und macht sie mit 
chmod +x /usr/bin/schultag
ausführbar.


Benutzung
=========
Das Skript wird entweder ohne (heute) oder mit genau einem ganzzahligen Parameter n ("in n Tagen") aufgerufen, wobei n=0 heute bedeutet (und dann weggelassen werden kann)

Beispiele (falls schultag nach /usr/bin/ kopiert wurde):
/usr/bin/schultag && echo "Heute ist Schule" 
/usr/bin/schultag || echo "Heute ist schulfrei"

/usr/bin/schultag 1 && echo "Morgen ist Schule" 
/usr/bin/schultag 1 || echo "Morgen ist schulfrei"

/usr/bin/schultag 250 && echo "In 250 Tagen ist Schule" || echo "In 250 Tagen ist schulfrei"
