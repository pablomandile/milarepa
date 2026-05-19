// Aplica variantes `dark:` a clases Tailwind existentes en archivos .vue.
// Diseñado para Fase 4 del plan de modo oscuro: barrido masivo idempotente.
//
// Uso: node scripts/apply-dark-variants.mjs

import { readFileSync, writeFileSync } from 'node:fs';
import { glob } from 'glob';
import path from 'node:path';

const ROOT = path.resolve(process.cwd());

const EXCLUDE_GLOBS = [
    // Páginas públicas — fuera de alcance del modo oscuro
    'resources/js/Pages/Welcome.vue',
    'resources/js/Pages/GridActividades/**',
    'resources/js/Pages/Clases/ShowPublic.vue',
    'resources/js/Pages/OracionesCantadas/ShowPublic.vue',
    'resources/js/Pages/Membresias/PublicIndex.vue',
    'resources/js/Pages/ActividadesOnline.vue',
    // Archivos ya tratados manualmente
    'resources/js/Layouts/AppLayout.vue',
    'resources/js/Components/AuthenticationCard.vue',
    'resources/js/Components/Dropdown.vue',
    'resources/js/Components/DropdownLink.vue',
    'resources/js/Components/ThemeToggle.vue',
    'resources/js/Pages/TiposActividad/Index.vue',
    'resources/js/Pages/MiCaminoBudista/Index.vue',
];

// Pares: [clase base, clase dark equivalente (sin el prefijo "dark:")]
const PAIRS = [
    // Backgrounds
    ['bg-white', 'bg-gray-800'],
    ['bg-gray-100', 'bg-gray-900'],
    ['bg-gray-50', 'bg-gray-800/50'],

    // Text
    ['text-gray-900', 'text-gray-100'],
    ['text-gray-800', 'text-gray-100'],
    ['text-gray-700', 'text-gray-300'],
    ['text-gray-600', 'text-gray-400'],

    // Borders
    ['border-gray-100', 'border-gray-700'],
    ['border-gray-200', 'border-gray-700'],
    ['border-gray-300', 'border-gray-600'],

    // Divide
    ['divide-gray-200', 'divide-gray-700'],
];

function escapeRegex(s) {
    return s.replace(/[.*+?^${}()|[\]\\/]/g, '\\$&');
}

/**
 * Procesa una cadena de clases (contenido de class="...") y agrega
 * variantes dark idempotentemente.
 * - No matchea variantes con prefijo (hover:bg-white) ni opacidad (bg-white/50).
 * - Si el atributo ya tiene la variante dark, no la duplica.
 */
function processClassString(classStr) {
    let result = classStr;
    for (const [base, darkSuffix] of PAIRS) {
        const darkClass = `dark:${darkSuffix}`;

        if (result.includes(darkClass)) continue;

        // Negative lookbehind: no precedida por word char, slash, dos puntos o guión
        //   (excluye hover:bg-white, dark:bg-white, etc.)
        // Negative lookahead: no seguida por word char, slash o guión
        //   (excluye bg-white/50, bg-white-foo, bg-whiteN)
        const pattern = new RegExp(
            `(?<![\\w/:-])${escapeRegex(base)}(?![\\w/-])`,
            'g'
        );

        if (!pattern.test(result)) continue;

        // Reset porque test() consumió el lastIndex
        const pattern2 = new RegExp(
            `(?<![\\w/:-])${escapeRegex(base)}(?![\\w/-])`,
            'g'
        );

        result = result.replace(pattern2, `${base} ${darkClass}`);
    }
    return result;
}

function processFile(content) {
    let totalReplacements = 0;
    const updated = content.replace(
        /(\bclass=)("([^"]*)"|'([^']*)')/g,
        (_, prefix, _quoted, dq, sq) => {
            const classes = dq !== undefined ? dq : sq;
            const quote = dq !== undefined ? '"' : "'";
            const newClasses = processClassString(classes);
            if (newClasses !== classes) {
                totalReplacements++;
            }
            return `${prefix}${quote}${newClasses}${quote}`;
        }
    );
    return { updated, totalReplacements };
}

const files = await glob('resources/js/**/*.vue', {
    cwd: ROOT,
    ignore: EXCLUDE_GLOBS,
});

let changedFilesCount = 0;
let totalAttrsModified = 0;
const changedFiles = [];

for (const relPath of files) {
    const absPath = path.join(ROOT, relPath);
    const original = readFileSync(absPath, 'utf8');
    const { updated, totalReplacements } = processFile(original);

    if (updated !== original) {
        writeFileSync(absPath, updated, 'utf8');
        changedFilesCount++;
        totalAttrsModified += totalReplacements;
        changedFiles.push({ file: relPath, attrs: totalReplacements });
    }
}

console.log(`\n${changedFilesCount}/${files.length} archivos modificados`);
console.log(`${totalAttrsModified} class-attributes actualizados\n`);
console.log('Top 20 archivos por cantidad de cambios:');
changedFiles
    .sort((a, b) => b.attrs - a.attrs)
    .slice(0, 20)
    .forEach(({ file, attrs }) => {
        console.log(`  ${attrs.toString().padStart(4)}  ${file}`);
    });
