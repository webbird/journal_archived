                <h1>Journal</h1>
                <h2>Inhalt</h2>
                <ul>
                    <li><a href="#d1">Allgemein</a></li>
                    <li><a href="#d2">Download</a></li>
                    <li><a href="#d3">Lizenz</a></li>
                    <li><a href="#d4">Systemvoraussetzungen</a></li>
                    <li><a href="#d5">Installation/Update</a></li>
                    <li><a href="#d6">Beiträge erstellen/bearbeiten</a></li>
                    <li><a href="#d7">Einstellungen</a></li>

                </ul>

                <h2 id="d1">Allgemein</h2>
                <p>News with images (kurz: NWI) ermöglicht das Erstellen von News-Seiten bzw. -Beiträgen und bietet u.a. folgende Funktionen:</p>
                <ul>
                    <li>Beitragsbild</li>
                    <li>integrierte Bildergalerie (Masonry oder Fotorama)</li>
                    <li>optionaler 2. Inhaltsbereich</li>
                    <li>Sortieren von Beiträgen mit Drag &amp; Drop</li>
                    <li>Verschieben/Kopieren von Beiträgen zwischen Gruppen und Abschnitten</li>
                    <li>Import von Beiträgen und Einstellungen aus dem Modul "Topics" sowie "News" ("klassisches Newsmodul" bis Version 3.5.12)</li>
                </ul>

                <h2 id="d2">Download</h2>
                <p>Das Modul ist ab WBCE CMS 1.4 ein Coremodul und standardmäßig installiert. Darüber hinaus ist der Download im <a href="https://addons.wbce.org">WBCE CMS Add-On Repository</a> verfügbar.</p>

                <h2 id="d3">Lizenz</h2>
                <p>NWI steht unter der <a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU General Public License (GPL) v3.0</a>.</p>

                <h2 id="d4">Systemvoraussetzungen</h2>
                <p>NWI hat keine besonderen Systemvoraussetzungen. Wenn WBCE CMS funktioniert, sollte auch NWI laufen. </p>

                <h2 id="d5">Installation/Update</h2>
                <ol>
                    <li>Sofern erforderlich, aktuelle Version aus dem  <a href="https://addons.wbce.org">AOR</a> herunterladen</li>
                    <li>Wie jedes andere WBCE-Modul auch über Erweiterungen &gt; Module installieren bzw. aktualisieren </li>
                </ol>

                <h2 id="d6">Beiträge erstellen und bearbeiten</h2>
                <h3 id="d61">Loslegen und Schreiben</h3>

                <ol>
                    <li>Ggfs. eine neue Seite mit &quot;Journal&quot; anlegen</li>
                    <li>Um einen neuen Beitrag zu erstellen, auf die Schaltfläche "Beitrag verfassen" klicken. Zum Bearbeiten des Inhalts eines vorhandenen Beitrags auf dessen Titel klicken.</li>
                    <li>Überschrift und ggf. weitere Felder ausfüllen, ggf. Bilder auswählen. Die Funktion der Eingabefelder ist wohl selbsterklärend.</li>
                    <li>Auf &quot;Speichern&quot; oder &quot;Speichern und zurück&quot; klicken</li>
                    <li>Schritte 2. - 4. ein paar Mal wiederholen und sich das ganze im Frontend anschauen</li>
                </ol>
                <p>Grundsätzlich kann NWI mit anderen Modulen auf einer Seite bzw. in einem Block kombiniert werden, es kann dann aber wie bei jedem Modul, das eigene Detailseiten generiert, zu Ergebnissen kommen, die nicht dem Erwarteten/Erwünschten entsprechen.</p>

                <h3 id="d62">Bilder im Beitrag</h3>
                <p>Für jeden Beitrag kann ein Beitragsbild hinterlegt werden, das auf der Übersichtsseite und ggfs. der Beitragsseite angezeigt wird. Darüber hinaus ist es möglich, beliebig viele Bilder zu einem Beitrag zu hinterlegen, die als Bildergalerie angezeigt werden. Die Galeriedarstellung erfolgt entweder als Fotorama-Galerie (Thumbnails, Bild über die gesamte Breite) oder als Masonry-Galerie (Bildermosaik). </p>
                <p>Welches Galeriescript verwendet wird, wird für alle Beiträge in den Einstellungen des jeweiligen Abschnitts festgelegt.</p>
                <p>Die Galeriebilder werden hochgeladen, sobald der Beitrag gespeichert wird, und können dann mit Bildunterschriften versehen, per Drag&amp;Drop umsortiert oder auch wieder gelöscht werden.</p>
                <p>Beim Upload von Dateien mit gleichen Namen wie bereits vorhandenen Bildern werden die vorhandenen Dateien nicht überschrieben, sondern bei den nachfolgenden Dateien wird eine fortlaufende Nummerierung ergänzt (bild.jpg, bild_1.jpg usw.)</p>
                <p>Die Verwaltung der Bilder erfolgt nur über den Beitrag, nicht über die WBCE-Medienverwaltung, da NWI sonst nicht &quot;weiß&quot;, wo welche Bilder hingehören/fehlen usw.</p>

                <h3 id="d63">Gruppen</h3>
                <p>Beiträge können Gruppen zugeordnet werden. Dies hat einerseits Einfluss auf die Reihenfolge (die Beiträge werden erst nach Gruppe und dann nach einem weiteren anzugebenden Kriterium sortiert), und ermöglicht andererseits, themenspezifische Übersichtsseiten zu generieren. Diese können dann über die URL der NWI-Seite mit dem Parameter g?=GROUP_ID, also z.B. news.php?g=2 angesteuert werden.</p>
                <p>Ein Beitrag kann immer nur einer Gruppe zugeordnet sein.</p>
                <p>Einzelne oder mehrere Beiträge können von einer Gruppe in eine andere kopiert und verschoben werden.</p>

                <h3 id="d64">Stichworte</h3>
                <p>Diese Funktion steht nur zur Verfügung, wenn bei den Einstellungen der "Expertenmodus" aktiviert wurde und Stichworte angelegt wurden.</p>
                <p>Beiträge können einem oder mehreren Stichworten zugeordnet werden. Diese Stichworte werden dann je nach Konfiguration im Frontend auf der Beitragsübersicht und/oder der Detailansicht angezeigt und sind jeweils mit der Übersicht aller Beiträge zu diesem Stichwort verlinkt.</p>
                <p>Stichworte aus der Beitragsübersicht heraus zentral für alle Beiträge im Abschnitt zur Verfügung gestellt (Reiter "Stichworte") und können dann in der Beitrags-Detailansicht ausgewählt werden.</p>
                <p>Globale Stichworte stehen in allen NWI-Abschnitten zur Verfügung, also auch auf anderen Seiten des Auftritts.</p>
                <p>Nach dem Anlegen können Stichworte bearbeitet/geändert werden, ebenso ist es möglich, diesen eigene Farben zuzuweisen.</p>

                <h3 id="d65">2. Block</h3>
                <p>Diese Funktion steht nur zur Verfügung, wenn bei den Einstellungen der "Expertenmodus" aktiviert wurde und die Verwendung des 2. Blocks aktiviert ist.</p>
                <p>Sofern vom Template unterstützt, können Inhalte in einem zweiten Block (z.B. einer Randspalte) dargestellt werden. Dabei kann es sich entweder um bei den Einstellungen hinterlegte, wiederkehrende Inhalte handeln, beitragsspezifische Inhalte (Beitragsbild, Anreißertext o.ä.) oder direkt im Beitrag hinterlegte Texte, die im Eingabefeld für den 2. Block eingetragen wurden.</p>

                <h3 id="d66">Importfunktion</h3>
                <p>So lange noch kein Beitrag im jeweiligen NWI-Abschnitt erstellt wurde, können Beiträge aus anderen NWI-Abschnitten, News 3.x sowie Topics automatisch importiert werden.
                Die Seiteneinstellungen werden mit übernommen. Beim Import von Topics-Beiträgen sind aber noch manuelle Nacharbeiten erforderlich, sofern bei Topics die &quot;Additional Images&quot;-Funktion genutzt wurde.</p>

                <h3 id="d67">Beiträge kopieren / verschieben</h3>
                <p>Aus der Beitragsübersicht im Backend heraus können einzelne, mehrere markierte oder alle (markierten) Beiträge innerhalb eines Abschnitts kopiert oder zwischen unterschiedlichen Abschnitten (auch auf unterschiedlichen Seiten) kopiert oder verschoben werden. Kopierte Beiträge sind stets zunächst im Frontend nicht sichtbar (Auswahl Aktiv: &quot;nein&quot;).</p>

                <h3  id="d68">Beiträge löschen</h3>
                <p>Aus der Beitragsübersicht können einzelne, mehrere markierte oder alle (markierten) Beiträge gelöscht werden. Nach der Bestätigung der Rückfrage sind die betreffenden Beiträge unwiderruflich <strong>VERNICHTET</strong>, es gibt <strong>keinen</strong> Papierkorb!</p>


                <h2 id="d7">Einstellungen</h2>

                <h3 id="d71">Expertenmodus</h3>
                <p>Wird der "Expertenmodus" aktiviert, so stehen zusätzliche Eingabefelder bei den Einstellungen (2. Block), in der Beitragsübersicht (Stichworte) sowie Beitragsdetailansicht (Stichwortzuweisung, 2. Block) zur Verfügung.</p>
                <p><strong>Achtung: </strong>Beim Wechsel zwischen aktiviertem und deaktivierten Expertenmodus erfolgt jeweils die Rückkehr zur Beitragsübersicht, andere Änderungen an den Einstellungen werden dabei <strong>nicht</strong> gespeichert. </p>

                <h3 id="d72">Übersichtsseite</h3>
                <ul>
                    <li><strong>Sortierung</strong>: Festlegung der Reihenfolge der Beiträge (Benutzerdefiniert = manuelle Festlegung, Beiträge erscheinen so, wie sie im Backend angeordnet werden; Startdatum / Ablaufdatum / eingetragen (=Erstelldatum) / Eintrags-ID: jeweils absteigend nach entsprechendem Kriterium) </li>
                    <li><strong>Nachrichten pro Seite</strong>: Auswahl, wie viele Einträge (Teaserbild/Text) pro Seite angezeigt werden sollen</li>
                    <li><strong>Kopfzeile, Beitrag Schleife, Fußzeile</strong>: HTML-Code zur Formatierung der Anzeige</li>
                    <li><strong>Vorschaubild Größe ändern auf</strong> Breite/Höhe des Bildes in Pixeln. Bei Änderungen erfolgt <strong>keine</strong> automatische Neuberechnung, es ist also sinnvoll, sich im voraus Gedanken über die gewünschte Größe zu machen und dann den Wert nicht mehr zu ändern. <br />
                    Das Beitragsbild steht <strong>nur</strong> in der angegebenen Auflösung zur Verfügung. Soll es in verschiedenen Größen (klein auf der Übersichtsseite, größer auf der Beitragsseite) verwendet werden, die Bildgröße auf den Wert für die größere Darstellung setzen und das Bild auf der Übersichtsseite per CSS verkleinern.</li>
                </ul>
                <p>Erlaubte Platzhalter:</p>
                <h4 id="d721">Kopfzeile/Fußzeile</h4>
                <ul>
                    <li>[NEXT_PAGE_LINK] &quot;Nächste Seite&quot;, verlinkt zur nächsten Seite (bei Aufteilung der Übersichtsseite auf mehrere Seiten), </li>
                    <li>[NEXT_LINK], &quot;Nächste&quot;, s.o.,</li>
                    <li>[PREVIOUS_PAGE_LINK], &quot;Vorherige Seite&quot;, s.o., </li>
                    <li>[PREVIOUS_LINK],&quot;Vorherige&quot;, s.o.,</li>
                    <li>[OUT_OF], [OF], &quot;x von y&quot;,</li>
                    <li>[DISPLAY_PREVIOUS_NEXT_LINKS] &quot;hidden&quot; / &quot;visible&quot;, je nach dem, ob Paginierung erforderlich ist</li>
                    <li>[BACK] URL der News-Übersichtsseite</li>
                    <li>[TEXT_BACK] &quot;Zurück zur Übersicht&quot;</li>
                </ul>

                <h4 id="d722">Beitrag Schleife</h4>
                <ul>
                <li>[PAGE_TITLE] Überschrift der Seite,</li>
                <li>[GROUP_ID] ID der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;0&quot;</li>
                <li>[GROUP_TITLE] Titel der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;&quot;,</li>
                <li>[GROUP_IMAGE] Bild (&lt;img src.../&gt;) der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;&quot;,</li>
                <li>[DISPLAY_GROUP] <em>inherit</em> oder <em>none</em>,</li>
                <li>[DISPLAY_IMAGE] <em>inherit</em> oder <em>none</em>,</li>
                <li>[TITLE] Titel (Überschrift) des Beitrags,</li>
                <li>[IMAGE] Beitragsbild (&lt;img src=... /&gt;),</li>
                <li>[SHORT] Kurztext,</li>
                <li>[LINK] Link zur Beitrags-Detailansicht,</li>
                <li>[MODI_DATE] Datum der letzten Änderung des Beitrags,</li>
                <li>[MODI_TIME] Zeitpunkt (Uhrzeit) der letzten Änderung des Beitrags,</li>
                <li>[CREATED_DATE] Datum, wann der Beitrag erstellt wurde,</li>
                <li>[CREATED_TIME] Uhrzeit, zu der der Beitrag erstellt wurde,</li>
                <li>[PUBLISHED_DATE] Startdatum,</li>
                <li>[PUBLISHED_TIME] Startuhrzeit,</li>
                <li>[USER_ID] ID des Erstellers des Beitrags,</li>
                <li>[USERNAME] Benutzername des Erstellers des Beitrags,</li>
                <li>[DISPLAY_NAME] Anzeigename des Erstellers des Beitrags,</li>
                <li>[EMAIL] Mailadresse des Erstellers des Beitrags,</li>
                <li>[TEXT_READ_MORE] &quot;Details anzeigen&quot;,</li>
                <li>[SHOW_READ_MORE], <em>hidden</em> oder <em>visible</em>,</li>
                <li>[GROUP_IMAGE_URL] URL des Gruppen-Bildes,</li>
                <li>[CONTENT_LONG] Langtext,</li>
                <li>[TAGS] Dem Beitrag zugeordnete Stichworte (Tags)</li>
                </ul>
                <h3 id="d73">Beitragsansicht</h3>
                <ul>
                <li><strong>Nachrichten-Kopfzeile, -Inhalt, -Fußzeile, Block 2</strong>: HTML-Code zur Formatierung der Anzeige</li>
                </ul>
                <p>Erlaubte Platzhalter:</p>
                <h4 id="d731">Nachrichten-Kopfzeile, Nachrichten-Fußzeile, Block 2</h4>
                <ul>
                <li>[PAGE_TITLE] Überschrift der Seite,</li>
                <li>[GROUP_ID] ID der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;0&quot;</li>
                <li>[GROUP_TITLE] Titel der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;&quot;,</li>
                <li>[GROUP_IMAGE] Bild (&lt;img src.../&gt;) der Gruppe, der der Beitrag zugeordnet ist, bei Beiträgen ohne Gruppe &quot;&quot;,</li>
                <li>[DISPLAY_GROUP] <em>inherit</em> oder <em>none</em>,</li>
                <li>[DISPLAY_IMAGE] <em>inherit</em> oder <em>none</em>,</li>
                <li>[TITLE] Titel (Überschrift) des Beitrags,</li>
                <li>[IMAGE] Beitragsbild (&lt;img src=... /&gt;),</li>
                <li>[IMAGE_URL] URL des Beitragsbilds (https://example.com/media/.journal/filename.jpg),</li>
                <li>[CONTENT_SHORT] Kurztext,</li>
                <li>[MODI_DATE] Datum der letzten Änderung des Beitrags,</li>
                <li>[MODI_TIME] Zeitpunkt (Uhrzeit) der letzten Änderung des Beitrags,</li>
                <li>[CREATED_DATE] Datum, wann der Beitrag erstellt wurde,</li>
                <li>[CREATED_TIME] Uhrzeit, zu der der Beitrag erstellt wurde,</li>
                <li>[PUBLISHED_DATE] Startdatum,</li>
                <li>[PUBLISHED_TIME] Startuhrzeit,</li>
                <li>[USER_ID] ID des Erstellers des Beitrags,</li>
                <li>[USERNAME] Benutzername des Erstellers des Beitrags,</li>
                <li>[DISPLAY_NAME] Anzeigename des Erstellers des Beitrags,</li>
                <li>[EMAIL] Mailadresse des Erstellers des Beitrags,</li>
                <li>[TAGS] Dem Beitrag zugeordnete Stichworte (Tags)</li>
                </ul>
                <h4 id="d732">Nachrichten-Inhalt</h4>
                <ul>
                <li>[CONTENT] kompletter Beitragsinhalt (SHORT+LONG) (HTML)<,/li>
                <li>[IMAGES] Bilder / Galerie-HTML,</li>
                <li>[IMAGE_URL] URL des Beitragsbilds (https://example.com/media/.journal/filename.jpg),</li>
                <li>[CONTENT_SHORT] Kurztext,</li>
                <li>[CONTENT_LONG] Langtext, </li>
                <li>[TAGS] Dem Beitrag zugeordnete Stichworte (Tags)</li>
                </ul>
                <h3 id="d74">Galerie-/Bild-Einstellungen</h3>
                <ul>
                <li><strong>Bildergalerie</strong>: Auswahl des zu verwendenden Galeriescripts. Bitte beachten, dass eventuell vorgenommene individuelle Anpassungen am Galeriecode im Feld Nachrichten-Inhalt bei einer Änderung verloren gehen.</li>
                <li><strong>Bild Schleife</strong>: HTML-Code für die Darstellung eines einzelnen Bildes, muss zum jeweiligen Galeriescript passen</li>
                <li><strong>Max. Bildgröße in Bytes</strong>: Dateigröße pro Bilddatei, warum das jetzt in Bytes und nicht in lesbareren KB oder MB angegeben werden muss, weiß ich gerade nicht</li>
                <li><strong>Galeriebilder / Thumbnailbilder Größe ändern auf Breite x Höhe</strong>: genau selbige. Bei Änderungen erfolgt <strong>keine</strong> automatische Neuberechnung, es ist also sinnvoll, sich im voraus Gedanken über die gewünschte Größe zu machen und dann den Wert nicht mehr zu ändern.</li>
                <li><strong>Beschneiden</strong>: Siehe Erläuterung auf der Seite.</li>
                </ul>
                <h3 id="d75">2. Block</h3>
                <p>Optional kann ein 2. Block angezeigt werden, sofern das Template dies unterstützt. In diesem wird dann entweder der hier hinterlegte Inhalt <strong>oder</strong> beim Beitrag hinterlegter Text angezeigt (beides gleichzeitig ist nicht vorgesehen). Das Eingabefeld wird nur angezeigt, wenn der 2. Block aktiviert ist.</p>
