Konfiguration
=============
Im Skript oben muss das Bundesland konfiguriert werden, für das die Abfrage stattfindet.
Möglich sind folgende Codes:

  BW  
  BY  
  BE  
  BB  
  HB  
  HH  
  HE  
  MV  
  NI  
  NW  
  RP  
  SL  
  SN  
  ST  
  SH  
  TH  

Benutzung
=========
Das Skript wird entweder ohne (heute) oder mit genau einem ganzzahligen Parameter n ("in n Tagen") aufgerufen, wobei n=0 heute bedeutet (und dann weggelassen werden kann)

Beispiele (falls schultag nach /usr/bin/ kopiert wurde):
/usr/bin/schultag && echo "Heute ist Schule" 
/usr/bin/schultag || echo "Heute ist schulfrei"

/usr/bin/schultag 1 && echo "Morgen ist Schule" 
/usr/bin/schultag 1 || echo "Morgen ist schulfrei"

/usr/bin/schultag 250 && echo "In 250 Tagen ist Schule" || echo "In 250 Tagen ist schulfrei"
