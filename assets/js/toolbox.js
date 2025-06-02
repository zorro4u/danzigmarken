/**
 * Druckoption bei Tabelle, Details, Bearbeiten
 */
function prn_toogle(ID, PRN) {
    jQuery.ajax({
        type: 'POST',
        url: '/assets/inc/printoption.php',
        data: {id: ID, prn: PRN}
    })
}


/**
 * Settings
 */
'use strict';
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('resize', messen);
    messen();

    function messen() {
        document.getElementById('clientW')
            .textContent = document.querySelector('html')
            .clientWidth;
        document.getElementById('innerW')
            .textContent = window.innerWidth;
        document.getElementById('outerW')
            .textContent = window.outerWidth;
        document.getElementById('clientH')
            .textContent = document.querySelector('html')
            .clientHeight;
        document.getElementById('innerH')
            .textContent = window.innerHeight;
        document.getElementById('outerH')
            .textContent = window.outerHeight;
        document.getElementById('screenW')
            .textContent = screen.width;
        document.getElementById('availW')
            .textContent = screen.availWidth;
        document.getElementById('screenH')
            .textContent = screen.height;
        document.getElementById('availH')
            .textContent = screen.availHeight;

        document.getElementById('matchMedia')
            .textContent = window.matchMedia().media;
    }
});


/**
 * Admin
 */
// href mit ?Parameter und #Sprungmarke
// -> class = "anchor_extended"
//
window.addEventListener("load", function () {

// Falls der Browser nicht automatisch zum gewünschten Element springt
// erledigt das Javascript.
if (window.location.hash)
    window.location.href = window.location.hash;

// Die Steuerelemente, welche den Mechanismus auslösen sollen, werden selektiert,
// sie müssen via class="anchor_extended" ausgezeichnet werden.
var anchors = document.getElementsByClassName("anchor_extended");

for (var i = 0; i < anchors.length; i++) {
    anchors[i].addEventListener("click", function (event) {
        // Prevent the anchor to perform its href-jump.
        event.preventDefault();
        // Variablen vordefinieren.
        var target = {},
        current = {}
        path = window.location.origin;

        // URL und Hash des Ziels extrahieren. Unterschieden wird zwischen a-Tag's dessen href
        // ausgelesen wird und anderen Elementen (wie z.B. div), bei denen auf das data-href=""-Attribut
        // zugegriffen wird. Für den 2. Fall benötigen wir die eben definierte path-Variable
        // welche den absoluten Pfad enthält.
        target.href = this.href ? this.href.split("#") : (path + this.dataset.href).split("#");
        target.url = target.href.length > 2 ? target.href.slice(0, -1).join("#") : target.href[0];
        target.hash = target.href.length > 1 ? target.href[target.href.length - 1] : "";

        // URL und Hash der aktuellen Datei.
        current.url = window.location.href.split("#").slice(0, -1).join("#");
        current.hash = window.location.hash;

        if (current.url == target.url)
            if (current.hash == target.hash)
                // Dateiname und Hash sind identisch, die Seite
                // wird lediglich neu geladen.
                window.location.reload();
            else {
                // Der Hash unterscheidet sich, dem location-Objekt
                // wird dieser zugeteilt, anschließend wird die Seite
                // neu geladen.
                window.location.hash = target.hash;
                window.location.reload();
            }
        else
            // Der Dateiname unterscheidet sich, _GET-Daten wurden geändert
            // oder eine andere Datei soll aufgerufen werden, es wird lediglich
            // auf diese Datei verwiesen.
            window.location.href = this.href;
    });
}

});