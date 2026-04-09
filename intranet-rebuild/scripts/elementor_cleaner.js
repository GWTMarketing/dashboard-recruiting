#!/usr/bin/env node
/**
 * Elementor Content Cleaner
 *
 * Bereinigt WordPress-Inhalte von Elementor-spezifischem Markup,
 * damit sie sauber in Bricks Builder weiterverwendet werden koennen.
 *
 * Funktionsweise:
 *   1. Verbindet sich ueber die WordPress REST API
 *   2. Laedt alle Posts, Pages und Custom Post Types
 *   3. Entfernt Elementor-Shortcodes, CSS-Klassen, Wrapper-Divs
 *   4. Exportiert bereinigten Content als JSON
 *   5. Optional: Schreibt bereinigten Content zurueck (--write)
 *
 * Verwendung:
 *   node elementor_cleaner.js --wp-url=https://intranet.firma.at --user=admin --app-password=xxxx
 *   node elementor_cleaner.js --wp-url=https://intranet.firma.at --user=admin --app-password=xxxx --write
 *   node elementor_cleaner.js --wp-url=https://intranet.firma.at --user=admin --app-password=xxxx --output=./export
 *
 * Optionen:
 *   --wp-url         WordPress-URL (erforderlich)
 *   --user           Benutzername fuer die REST API (erforderlich)
 *   --app-password   Application Password (erforderlich)
 *   --write          Aenderungen tatsaechlich in WordPress schreiben (Standard: Dry-Run)
 *   --output         Ausgabeverzeichnis (Standard: ./output)
 *   --post-type      Bestimmten Post-Typ verarbeiten (Standard: alle)
 *
 * KEINE externen Abhaengigkeiten – nutzt nur Node.js eingebaute Module.
 *
 * @package GWT_Intranet
 */

const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');
const { URL } = require('url');

/* --------------------------------------------------------------------------
   Konfiguration aus Kommandozeilen-Argumenten
   -------------------------------------------------------------------------- */

function parseArgs() {
    const args = {};
    process.argv.slice(2).forEach(arg => {
        if (arg.startsWith('--')) {
            const [key, ...valueParts] = arg.slice(2).split('=');
            args[key] = valueParts.length > 0 ? valueParts.join('=') : true;
        }
    });
    return args;
}

const args = parseArgs();

const config = {
    wpUrl:       args['wp-url'] || '',
    user:        args['user'] || '',
    appPassword: args['app-password'] || '',
    write:       args['write'] === true,
    output:      args['output'] || './output',
    postType:    args['post-type'] || null,
};

// Pflichtfelder pruefen
if (!config.wpUrl || !config.user || !config.appPassword) {
    console.error('Fehler: --wp-url, --user und --app-password sind erforderlich.');
    console.error('');
    console.error('Verwendung:');
    console.error('  node elementor_cleaner.js --wp-url=https://intranet.firma.at --user=admin --app-password=xxxx');
    process.exit(1);
}

// Basic Auth Header erstellen
const authHeader = 'Basic ' + Buffer.from(`${config.user}:${config.appPassword}`).toString('base64');

/* --------------------------------------------------------------------------
   HTTP-Hilfsfunktionen (ohne externe Abhaengigkeiten)
   -------------------------------------------------------------------------- */

/**
 * Fuehrt einen HTTP(S)-Request aus und gibt das Ergebnis als JSON zurueck.
 *
 * @param {string} url      Vollstaendige URL.
 * @param {string} method   HTTP-Methode (GET, POST, PUT).
 * @param {object} body     Request-Body (optional).
 * @returns {Promise<object>}
 */
function apiRequest(url, method = 'GET', body = null) {
    return new Promise((resolve, reject) => {
        const parsedUrl = new URL(url);
        const client = parsedUrl.protocol === 'https:' ? https : http;

        const options = {
            hostname: parsedUrl.hostname,
            port: parsedUrl.port || (parsedUrl.protocol === 'https:' ? 443 : 80),
            path: parsedUrl.pathname + parsedUrl.search,
            method: method,
            headers: {
                'Authorization': authHeader,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        const req = client.request(options, (res) => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => {
                try {
                    const json = JSON.parse(data);
                    resolve({ status: res.statusCode, data: json, headers: res.headers });
                } catch (e) {
                    reject(new Error(`JSON-Parse-Fehler: ${e.message} (Status: ${res.statusCode})`));
                }
            });
        });

        req.on('error', reject);

        if (body) {
            req.write(JSON.stringify(body));
        }

        req.end();
    });
}

/**
 * Laedt alle Seiten eines paginierten REST-API-Endpoints.
 *
 * @param {string} baseUrl Basis-URL des Endpoints.
 * @returns {Promise<array>}
 */
async function fetchAllPages(baseUrl) {
    let allItems = [];
    let page = 1;
    let totalPages = 1;

    while (page <= totalPages) {
        const separator = baseUrl.includes('?') ? '&' : '?';
        const url = `${baseUrl}${separator}page=${page}&per_page=100`;

        const response = await apiRequest(url);

        if (response.status !== 200) {
            if (page === 1) {
                return []; // Leere Sammlung wenn Post-Typ leer
            }
            break;
        }

        if (Array.isArray(response.data)) {
            allItems = allItems.concat(response.data);
        }

        totalPages = parseInt(response.headers['x-wp-totalpages'] || '1', 10);
        page++;
    }

    return allItems;
}

/* --------------------------------------------------------------------------
   Elementor-Markup Bereinigung
   -------------------------------------------------------------------------- */

/**
 * Entfernt Elementor-spezifisches Markup aus dem HTML-Content.
 *
 * @param {string} content Der originale post_content.
 * @returns {string} Bereinigter Content.
 */
function cleanElementorMarkup(content) {
    if (!content) return '';

    let cleaned = content;

    // 1. Elementor-Shortcodes entfernen: [elementor-template ...], [elementor ...]
    cleaned = cleaned.replace(/\[elementor[^\]]*\]/gi, '');
    cleaned = cleaned.replace(/\[\/elementor[^\]]*\]/gi, '');

    // 2. Divi-Shortcodes entfernen (falls vorhanden): [et_pb_*]
    cleaned = cleaned.replace(/\[et_pb_[^\]]*\]/gi, '');
    cleaned = cleaned.replace(/\[\/et_pb_[^\]]*\]/gi, '');

    // 3. Elementor-spezifische Block-Kommentare entfernen
    cleaned = cleaned.replace(/<!-- wp:elementor[^>]*-->/gi, '');
    cleaned = cleaned.replace(/<!-- \/wp:elementor[^>]*-->/gi, '');

    // 4. data-elementor-* Attribute entfernen
    cleaned = cleaned.replace(/\s*data-elementor-[a-z-]*="[^"]*"/gi, '');
    cleaned = cleaned.replace(/\s*data-element_type="[^"]*"/gi, '');
    cleaned = cleaned.replace(/\s*data-widget_type="[^"]*"/gi, '');
    cleaned = cleaned.replace(/\s*data-id="[a-f0-9]+"/gi, '');
    cleaned = cleaned.replace(/\s*data-settings='[^']*'/gi, '');

    // 5. Elementor CSS-Klassen entfernen
    cleaned = cleaned.replace(/\s*class="[^"]*elementor[^"]*"/gi, (match) => {
        // Klassen die "elementor" enthalten entfernen, andere behalten
        const classes = match.match(/class="([^"]*)"/)[1]
            .split(/\s+/)
            .filter(cls => !cls.startsWith('elementor') && !cls.startsWith('e-') && !cls.startsWith('eicon-'));

        return classes.length > 0 ? ` class="${classes.join(' ')}"` : '';
    });

    // 6. Leere Elementor-Wrapper-Divs entfernen
    // Mehrere Durchlaeufe fuer verschachtelte leere Divs
    for (let i = 0; i < 5; i++) {
        cleaned = cleaned.replace(/<div[^>]*>\s*<\/div>/gi, '');
    }

    // 7. Leere Sections und Columns entfernen
    cleaned = cleaned.replace(/<section[^>]*>\s*<\/section>/gi, '');

    // 8. Elementor-spezifische Inline-Styles bereinigen (data-settings basierte)
    cleaned = cleaned.replace(/\s*style="[^"]*elementor[^"]*"/gi, '');

    // 9. Mehrfache Leerzeilen auf eine reduzieren
    cleaned = cleaned.replace(/\n{3,}/g, '\n\n');

    // 10. Fuehrende/nachfolgende Leerzeichen entfernen
    cleaned = cleaned.trim();

    return cleaned;
}

/**
 * Extrahiert Text-Content aus Elementor-JSON-Daten (_elementor_data).
 * Geht rekursiv durch alle Widgets und extrahiert lesbaren Content.
 *
 * @param {array|object} elements Elementor-Datenstruktur.
 * @returns {string} Extrahierter HTML-Content.
 */
function extractFromElementorData(elements) {
    if (!elements) return '';

    const parts = [];

    const processElement = (el) => {
        if (!el) return;

        // Widget-Content extrahieren
        if (el.widgetType && el.settings) {
            switch (el.widgetType) {
                case 'heading':
                    const tag = el.settings.header_size || 'h2';
                    const title = el.settings.title || '';
                    if (title) parts.push(`<${tag}>${title}</${tag}>`);
                    break;

                case 'text-editor':
                    if (el.settings.editor) parts.push(el.settings.editor);
                    break;

                case 'image':
                    if (el.settings.image && el.settings.image.url) {
                        const alt = el.settings.image.alt || '';
                        parts.push(`<img src="${el.settings.image.url}" alt="${alt}">`);
                    }
                    break;

                case 'icon-list':
                    if (el.settings.icon_list) {
                        const items = el.settings.icon_list
                            .map(item => `<li>${item.text || ''}</li>`)
                            .join('');
                        parts.push(`<ul>${items}</ul>`);
                    }
                    break;

                case 'button':
                    if (el.settings.text && el.settings.link && el.settings.link.url) {
                        parts.push(`<a href="${el.settings.link.url}">${el.settings.text}</a>`);
                    }
                    break;

                case 'video':
                    if (el.settings.youtube_url) {
                        parts.push(`<p>[Video: ${el.settings.youtube_url}]</p>`);
                    }
                    break;
            }
        }

        // Rekursiv in verschachtelte Elemente gehen
        if (el.elements && Array.isArray(el.elements)) {
            el.elements.forEach(processElement);
        }
    };

    if (Array.isArray(elements)) {
        elements.forEach(processElement);
    } else {
        processElement(elements);
    }

    return parts.join('\n\n');
}

/* --------------------------------------------------------------------------
   Hauptprogramm
   -------------------------------------------------------------------------- */

async function main() {
    console.log('========================================');
    console.log('  Elementor Content Cleaner v1.0');
    console.log('========================================');
    console.log(`  WordPress: ${config.wpUrl}`);
    console.log(`  Modus:     ${config.write ? 'SCHREIBEN (Aenderungen werden gespeichert!)' : 'DRY-RUN (nur Vorschau)'}`);
    console.log(`  Ausgabe:   ${config.output}`);
    console.log('');

    // Ausgabeverzeichnis erstellen
    if (!fs.existsSync(config.output)) {
        fs.mkdirSync(config.output, { recursive: true });
    }

    // Verfuegbare Post-Typen laden
    console.log('Lade Post-Typen...');
    const typesResponse = await apiRequest(`${config.wpUrl}/wp-json/wp/v2/types`);

    if (typesResponse.status !== 200) {
        console.error('Fehler: Konnte Post-Typen nicht laden. Bitte Zugangsdaten pruefen.');
        process.exit(1);
    }

    const postTypes = Object.keys(typesResponse.data).filter(type => {
        // Standard-WordPress-Typen und relevante Custom Post Types
        const include = ['post', 'page'];
        const exclude = ['attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'];

        if (config.postType) {
            return type === config.postType;
        }

        if (exclude.includes(type)) return false;

        // Alle nicht-ausgeschlossenen Typen verarbeiten
        return true;
    });

    console.log(`Gefundene Post-Typen: ${postTypes.join(', ')}`);
    console.log('');

    let totalProcessed = 0;
    let totalCleaned = 0;
    let totalErrors = 0;

    // Jeden Post-Typ verarbeiten
    for (const postType of postTypes) {
        const typeInfo = typesResponse.data[postType];
        const restBase = typeInfo.rest_base || postType;

        console.log(`--- ${postType} (REST: /wp/v2/${restBase}) ---`);

        // Alle Posts dieses Typs laden
        const posts = await fetchAllPages(`${config.wpUrl}/wp-json/wp/v2/${restBase}?status=publish,draft,private`);

        if (posts.length === 0) {
            console.log(`  Keine Eintraege gefunden.`);
            continue;
        }

        console.log(`  ${posts.length} Eintraege geladen.`);

        const cleanedPosts = [];

        for (const post of posts) {
            const originalContent = post.content ? post.content.rendered || '' : '';
            const rawContent = post.content ? post.content.raw || originalContent : '';

            // Elementor-Markup bereinigen
            let cleanedContent = cleanElementorMarkup(rawContent);

            // Pruefen ob sich etwas geaendert hat
            const hasChanged = cleanedContent !== rawContent;

            // Featured Image URL holen
            let featuredImageUrl = '';
            if (post.featured_media && post.featured_media > 0) {
                try {
                    const mediaResponse = await apiRequest(
                        `${config.wpUrl}/wp-json/wp/v2/media/${post.featured_media}`
                    );
                    if (mediaResponse.status === 200 && mediaResponse.data.source_url) {
                        featuredImageUrl = mediaResponse.data.source_url;
                    }
                } catch (e) {
                    // Bild konnte nicht geladen werden – kein kritischer Fehler
                }
            }

            // Export-Objekt erstellen
            const exportItem = {
                id:                 post.id,
                post_title:         post.title ? post.title.rendered || '' : '',
                post_content:       cleanedContent,
                post_content_raw:   rawContent,
                post_type:          postType,
                post_status:        post.status,
                post_date:          post.date,
                post_slug:          post.slug,
                featured_image_url: featuredImageUrl,
                was_cleaned:        hasChanged,
            };

            cleanedPosts.push(exportItem);
            totalProcessed++;

            if (hasChanged) {
                totalCleaned++;
                console.log(`  [BEREINIGT] ${post.title.rendered || post.slug} (ID: ${post.id})`);

                // Im Schreib-Modus: Content zurueckschreiben
                if (config.write) {
                    try {
                        await apiRequest(
                            `${config.wpUrl}/wp-json/wp/v2/${restBase}/${post.id}`,
                            'PUT',
                            { content: cleanedContent }
                        );
                        console.log(`             -> Gespeichert.`);
                    } catch (e) {
                        console.error(`             -> FEHLER beim Speichern: ${e.message}`);
                        totalErrors++;
                    }
                }
            }
        }

        // JSON-Export fuer diesen Post-Typ
        const outputFile = path.join(config.output, `clean_content_export_${postType}.json`);
        fs.writeFileSync(outputFile, JSON.stringify(cleanedPosts, null, 2), 'utf-8');
        console.log(`  Exportiert nach: ${outputFile}`);
        console.log('');
    }

    // Zusammenfassung
    console.log('========================================');
    console.log('  ZUSAMMENFASSUNG');
    console.log('========================================');
    console.log(`  Verarbeitet:  ${totalProcessed} Beitraege`);
    console.log(`  Bereinigt:    ${totalCleaned} Beitraege`);
    console.log(`  Fehler:       ${totalErrors}`);
    console.log(`  Modus:        ${config.write ? 'GESCHRIEBEN' : 'DRY-RUN (keine Aenderungen)'}`);
    console.log('');

    if (!config.write && totalCleaned > 0) {
        console.log('  Tipp: Fuehre den Befehl mit --write aus, um die Aenderungen zu speichern.');
    }
}

// Script starten
main().catch(err => {
    console.error('Unerwarteter Fehler:', err.message);
    process.exit(1);
});
