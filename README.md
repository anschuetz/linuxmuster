linuxmuster
===========

Alles, was das linuxmuster.net-Leben erleichtert...
  * Schultag -> Skript ermittelt, ob Schultag ist und führt davon abhängig einen Befehl aus.
  * WakeOnLan -> Miniskript schreibt des Nächtens alle MAC-Adressen mit IP-Adressen in eine Datei, damit das Paket wakeonlan (extra installieren!) mit IP-Adresse aufgerufen werden kann.
  * Bereinige_MRBS -> Wenn der Admin einen Eintrag in MRBS bucht, erscheint [administrator] vor diesem Eintrag. Das ist meist unerwünscht. Dieses Skript entfernt als täglicher Cronjob dieses Präfix (nur für Administrator).
  * wer_hat_quota_voll -> Ermittlung der Quotasünder per Cronjob mit Email an den Admin, falls es Nutzer mit überlaufener Quota gibt.
  * firefox-einstellungen-loeschen -> Dieses Skript löscht in allen Schüler- und/oder Lehrerhomeverzeichnissen die Firefox-Einstellungen. Was genau getan werden soll, wird nach dem Starten abgefragt.
  * webloganalyse -> die Access-Logs des Web-Proxy werden einfach analysiert, um schnell herauszufinden, wer welche Seiten aufgerufen hat.
 

