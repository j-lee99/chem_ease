<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>IUPAC Periodic Table – ChemEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
        }
        body {
            background: var(--light-gray);
            color: var(--dark-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
            padding-bottom: 2rem;
        }
        .pt-container {
            width: 100%;
            background: #fff;
            padding: var(--spacing-sm);
            margin: 0;
        }
        .pt-header {
            text-align: center;
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm);
        }
        .pt-header h2 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: var(--spacing-xs);
            line-height: 1.3;
        }
        .pt-header p {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
        }
        .legend-toggle {
            display: block;
            width: 100%;
            padding: var(--spacing-sm);
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 0.5rem;
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .legend-toggle:active {
            transform: scale(0.98);
        }
        .pt-legend {
            display: none;
            flex-wrap: wrap;
            gap: var(--spacing-xs);
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm);
            background: #f8f9fa;
            border-radius: 0.5rem;
        }
        .pt-legend.show {
            display: flex;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
            background: white;
            border-radius: 0.25rem;
            flex: 0 0 calc(50% - 0.125rem);
        }
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            border: 1px solid #999;
            flex-shrink: 0;
        }
        .table-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            margin-bottom: var(--spacing-md);
            border-radius: 0.5rem;
            background: #f1f1f1;
            padding: var(--spacing-xs);
        }
        .periodic-table {
            display: grid;
            grid-template-columns: repeat(18, minmax(30px, 1fr));
            gap: 2px;
            min-width: 100%;
            width: max-content;
        }
        .element {
            position: relative;
            aspect-ratio: 1;
            background: #fff;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.6rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            min-height: 30px;
            border: 1px solid #ddd;
            user-select: none;
            touch-action: manipulation;
        }
        .element:active {
            transform: scale(0.95);
            box-shadow: 0 2px 6px rgba(0,0,0,.2);
        }
        .atomic-number {
            font-size: 0.45rem;
            position: absolute;
            top: 1px;
            left: 2px;
            color: #666;
            font-weight: 500;
            line-height: 1;
        }
        .symbol {
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
            margin-top: 2px;
        }
        .name {
            font-size: 0.4rem;
            color: #555;
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
            font-weight: 500;
            line-height: 1;
        }
        .mass {
            display: none;
        }
        .placeholder {
            background: #e9ecef !important;
            cursor: default;
            font-size: 0.6rem;
            color: #666;
            border: 1px dashed #999;
        }
        .placeholder:active {
            transform: none;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        .alkali { background: #ff9999; }
        .alkaline { background: #ffcc99; }
        .transition { background: #ffff99; }
        .post-transition { background: #ccffcc; }
        .metalloid { background: #ccffff; }
        .nonmetal { background: #ffcccc; }
        .halogen { background: #ffffcc; }
        .noble-gas { background: #cc99ff; }
        .lanthanoid { background: #ffbfff; }
        .actinoid { background: #ff99cc; }
        .empty {
            background: transparent;
            border: none;
            box-shadow: none;
        }
        .series-container {
            display: grid;
            grid-template-columns: repeat(15, minmax(30px, 1fr));
            gap: 2px;
            width: max-content;
            max-width: 100%;
            margin: 0 auto var(--spacing-sm);
            background: #f1f1f1;
            border-radius: 0.5rem;
            padding: var(--spacing-xs);
        }
        .series-label {
            text-align: center;
            font-weight: 600;
            margin-top: var(--spacing-sm);
            margin-bottom: var(--spacing-xs);
            font-size: 0.75rem;
            color: #666;
        }
        .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,.3);
        }
        .modal-header {
            background: var(--primary-blue);
            color: white;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1rem;
        }
        .modal-title {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-title .symbol {
            font-size: 1.75rem;
            font-weight: 700;
        }
        .modal-body {
            padding: 1rem;
        }
        .modal-body table {
            font-size: 0.875rem;
        }
        .modal-body th {
            width: 40%;
            font-weight: 600;
            color: #495057;
        }
        .modal-dialog {
            margin: 0.5rem;
        }
        @media (min-width: 576px) {
            .pt-container {
                padding: var(--spacing-md);
                margin: var(--spacing-sm);
                border-radius: 1rem;
                box-shadow: 0 4px 12px rgba(0,0,0,.08);
            }
            .pt-header h2 {
                font-size: 1.5rem;
            }
            .pt-header p {
                font-size: 0.875rem;
            }
            .legend-toggle {
                display: none;
            }
            .pt-legend {
                display: flex;
            }
            .legend-item {
                flex: 0 0 auto;
                font-size: 0.75rem;
            }
            .periodic-table {
                grid-template-columns: repeat(18, minmax(35px, 1fr));
                gap: 3px;
            }
            .series-container {
                grid-template-columns: repeat(15, minmax(35px, 1fr));
                gap: 3px;
            }
            .element {
                font-size: 0.65rem;
                min-height: 35px;
            }
            .symbol {
                font-size: 1rem;
            }
            .name {
                font-size: 0.5rem;
            }
            .modal-dialog {
                margin: 1.75rem auto;
            }
        }
        @media (min-width: 768px) {
            .pt-container {
                padding: var(--spacing-lg);
                margin: var(--spacing-md);
            }
            .pt-header h2 {
                font-size: 1.75rem;
            }
            .pt-header p {
                font-size: 1rem;
            }
            .legend-item {
                font-size: 0.8rem;
                padding: 0.3rem 0.5rem;
            }
            .legend-color {
                width: 16px;
                height: 16px;
            }
            .periodic-table {
                grid-template-columns: repeat(18, minmax(42px, 1fr));
                gap: 3px;
                padding: var(--spacing-sm);
            }
            .series-container {
                grid-template-columns: repeat(15, minmax(42px, 1fr));
                gap: 3px;
                padding: var(--spacing-sm);
            }
            .element {
                font-size: 0.7rem;
                min-height: 42px;
                border-radius: 6px;
            }
            .element:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 12px rgba(0,0,0,.15);
            }
            .element:active {
                transform: scale(0.97);
            }
            .symbol {
                font-size: 1.05rem;
            }
            .name {
                font-size: 0.52rem;
                display: block;
            }
            .mass {
                display: block;
                font-size: 0.5rem;
                color: #777;
            }
        }
        @media (min-width: 992px) {
            .periodic-table {
                grid-template-columns: repeat(18, minmax(48px, 1fr));
                gap: 4px;
            }
            .series-container {
                grid-template-columns: repeat(15, minmax(48px, 1fr));
                gap: 4px;
            }
            .element {
                min-height: 48px;
            }
            .symbol {
                font-size: 1.1rem;
            }
            .name {
                font-size: 0.55rem;
            }
        }
        @media (min-width: 1200px) {
            .pt-container {
                max-width: 1400px;
                margin: var(--spacing-lg) auto;
            }
            .pt-header h2 {
                font-size: 2rem;
            }
            .legend-item {
                font-size: 0.85rem;
            }
            .legend-color {
                width: 18px;
                height: 18px;
            }
            .periodic-table {
                grid-template-columns: repeat(18, minmax(52px, 1fr));
            }
            .series-container {
                grid-template-columns: repeat(15, minmax(52px, 1fr));
            }
            .element {
                min-height: 52px;
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
<div class="pt-container">
    <div class="pt-header">
        <h2><i class="fas fa-table me-2"></i>IUPAC Periodic Table</h2>
        <p>Tap any element for details</p>
    </div>
    
    <button class="legend-toggle" onclick="document.querySelector('.pt-legend').classList.toggle('show')">
        <i class="fas fa-palette me-2"></i>Show Element Categories
    </button>
    
    <div class="pt-legend">
        <div class="legend-item"><div class="legend-color alkali"></div><span>Alkali Metals</span></div>
        <div class="legend-item"><div class="legend-color alkaline"></div><span>Alkaline Earth</span></div>
        <div class="legend-item"><div class="legend-color transition"></div><span>Transition</span></div>
        <div class="legend-item"><div class="legend-color post-transition"></div><span>Post-Transition</span></div>
        <div class="legend-item"><div class="legend-color metalloid"></div><span>Metalloids</span></div>
        <div class="legend-item"><div class="legend-color nonmetal"></div><span>Nonmetals</span></div>
        <div class="legend-item"><div class="legend-color halogen"></div><span>Halogens</span></div>
        <div class="legend-item"><div class="legend-color noble-gas"></div><span>Noble Gases</span></div>
        <div class="legend-item"><div class="legend-color lanthanoid"></div><span>Lanthanoids</span></div>
        <div class="legend-item"><div class="legend-color actinoid"></div><span>Actinoids</span></div>
    </div>
    
    <div class="table-wrapper">
        <div class="periodic-table" id="ptable"></div>
    </div>
    
    <div class="table-wrapper">
        <div class="series-label">Lanthanoids</div>
        <div class="series-container" id="lanthanoids"></div>
    </div>
    
    <div class="table-wrapper">
        <div class="series-label">Actinoids</div>
        <div class="series-container" id="actinoids"></div>
    </div>
</div>

<div class="modal fade" id="elementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="atomic-number" id="modal-atomic"></span>
                    <span class="symbol" id="modal-symbol"></span>
                    <span id="modal-name"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless">
                    <tr><th>Atomic Mass</th><td id="modal-mass"></td></tr>
                    <tr><th>Electron Config</th><td id="modal-config"></td></tr>
                    <tr><th>Category</th><td id="modal-category"></td></tr>
                    <tr><th>Discovered</th><td id="modal-year"></td></tr>
                    <tr><th>Phase (STP)</th><td id="modal-phase"></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

 <script>
    const elements = [
        {no:1,sym:"H",name:"Hydrogen",mass:1.008,cat:"nonmetal",config:"1s¹",year:"1766",phase:"gas"},
        {no:2,sym:"He",name:"Helium",mass:4.003,cat:"noble-gas",config:"1s²",year:"1895",phase:"gas"},
        {no:3,sym:"Li",name:"Lithium",mass:6.941,cat:"alkali",config:"[He] 2s¹",year:"1817",phase:"solid"},
        {no:4,sym:"Be",name:"Beryllium",mass:9.012,cat:"alkaline",config:"[He] 2s²",year:"1798",phase:"solid"},
        {no:5,sym:"B",name:"Boron",mass:10.81,cat:"metalloid",config:"[He] 2s² 2p¹",year:"1808",phase:"solid"},
        {no:6,sym:"C",name:"Carbon",mass:12.01,cat:"nonmetal",config:"[He] 2s² 2p²",year:"Ancient",phase:"solid"},
        {no:7,sym:"N",name:"Nitrogen",mass:14.01,cat:"nonmetal",config:"[He] 2s² 2p³",year:"1772",phase:"gas"},
        {no:8,sym:"O",name:"Oxygen",mass:16.00,cat:"nonmetal",config:"[He] 2s² 2p⁴",year:"1774",phase:"gas"},
        {no:9,sym:"F",name:"Fluorine",mass:19.00,cat:"halogen",config:"[He] 2s² 2p⁵",year:"1886",phase:"gas"},
        {no:10,sym:"Ne",name:"Neon",mass:20.18,cat:"noble-gas",config:"[He] 2s² 2p⁶",year:"1898",phase:"gas"},
        {no:11,sym:"Na",name:"Sodium",mass:22.99,cat:"alkali",config:"[Ne] 3s¹",year:"1807",phase:"solid"},
        {no:12,sym:"Mg",name:"Magnesium",mass:24.31,cat:"alkaline",config:"[Ne] 3s²",year:"1755",phase:"solid"},
        {no:13,sym:"Al",name:"Aluminium",mass:26.98,cat:"post-transition",config:"[Ne] 3s² 3p¹",year:"1825",phase:"solid"},
        {no:14,sym:"Si",name:"Silicon",mass:28.09,cat:"metalloid",config:"[Ne] 3s² 3p²",year:"1824",phase:"solid"},
        {no:15,sym:"P",name:"Phosphorus",mass:30.97,cat:"nonmetal",config:"[Ne] 3s² 3p³",year:"1669",phase:"solid"},
        {no:16,sym:"S",name:"Sulfur",mass:32.07,cat:"nonmetal",config:"[Ne] 3s² 3p⁴",year:"Ancient",phase:"solid"},
        {no:17,sym:"Cl",name:"Chlorine",mass:35.45,cat:"halogen",config:"[Ne] 3s² 3p⁵",year:"1774",phase:"gas"},
        {no:18,sym:"Ar",name:"Argon",mass:39.95,cat:"noble-gas",config:"[Ne] 3s² 3p⁶",year:"1894",phase:"gas"},
        {no:19,sym:"K",name:"Potassium",mass:39.10,cat:"alkali",config:"[Ar] 4s¹",year:"1807",phase:"solid"},
        {no:20,sym:"Ca",name:"Calcium",mass:40.08,cat:"alkaline",config:"[Ar] 4s²",year:"1808",phase:"solid"},
        {no:21,sym:"Sc",name:"Scandium",mass:44.96,cat:"transition",config:"[Ar] 3d¹ 4s²",year:"1879",phase:"solid"},
        {no:22,sym:"Ti",name:"Titanium",mass:47.87,cat:"transition",config:"[Ar] 3d² 4s²",year:"1791",phase:"solid"},
        {no:23,sym:"V",name:"Vanadium",mass:50.94,cat:"transition",config:"[Ar] 3d³ 4s²",year:"1801",phase:"solid"},
        {no:24,sym:"Cr",name:"Chromium",mass:52.00,cat:"transition",config:"[Ar] 3d⁵ 4s¹",year:"1797",phase:"solid"},
        {no:25,sym:"Mn",name:"Manganese",mass:54.94,cat:"transition",config:"[Ar] 3d⁵ 4s²",year:"1774",phase:"solid"},
        {no:26,sym:"Fe",name:"Iron",mass:55.85,cat:"transition",config:"[Ar] 3d⁶ 4s²",year:"Ancient",phase:"solid"},
        {no:27,sym:"Co",name:"Cobalt",mass:58.93,cat:"transition",config:"[Ar] 3d⁷ 4s²",year:"Ancient",phase:"solid"},
        {no:28,sym:"Ni",name:"Nickel",mass:58.69,cat:"transition",config:"[Ar] 3d⁸ 4s²",year:"1751",phase:"solid"},
        {no:29,sym:"Cu",name:"Copper",mass:63.55,cat:"transition",config:"[Ar] 3d¹⁰ 4s¹",year:"Ancient",phase:"solid"},
        {no:30,sym:"Zn",name:"Zinc",mass:65.38,cat:"transition",config:"[Ar] 3d¹⁰ 4s²",year:"Ancient",phase:"solid"},
        {no:31,sym:"Ga",name:"Gallium",mass:69.72,cat:"post-transition",config:"[Ar] 3d¹⁰ 4s² 4p¹",year:"1875",phase:"solid"},
        {no:32,sym:"Ge",name:"Germanium",mass:72.64,cat:"metalloid",config:"[Ar] 3d¹⁰ 4s² 4p²",year:"1886",phase:"solid"},
        {no:33,sym:"As",name:"Arsenic",mass:74.92,cat:"metalloid",config:"[Ar] 3d¹⁰ 4s² 4p³",year:"Ancient",phase:"solid"},
        {no:34,sym:"Se",name:"Selenium",mass:78.96,cat:"nonmetal",config:"[Ar] 3d¹⁰ 4s² 4p⁴",year:"1817",phase:"solid"},
        {no:35,sym:"Br",name:"Bromine",mass:79.90,cat:"halogen",config:"[Ar] 3d¹⁰ 4s² 4p⁵",year:"1826",phase:"liquid"},
        {no:36,sym:"Kr",name:"Krypton",mass:83.80,cat:"noble-gas",config:"[Ar] 3d¹⁰ 4s² 4p⁶",year:"1898",phase:"gas"},
        {no:37,sym:"Rb",name:"Rubidium",mass:85.47,cat:"alkali",config:"[Kr] 5s¹",year:"1861",phase:"solid"},
        {no:38,sym:"Sr",name:"Strontium",mass:87.62,cat:"alkaline",config:"[Kr] 5s²",year:"1790",phase:"solid"},
        {no:39,sym:"Y",name:"Yttrium",mass:88.91,cat:"transition",config:"[Kr] 4d¹ 5s²",year:"1794",phase:"solid"},
        {no:40,sym:"Zr",name:"Zirconium",mass:91.22,cat:"transition",config:"[Kr] 4d² 5s²",year:"1789",phase:"solid"},
        {no:41,sym:"Nb",name:"Niobium",mass:92.91,cat:"transition",config:"[Kr] 4d⁴ 5s¹",year:"1801",phase:"solid"},
        {no:42,sym:"Mo",name:"Molybdenum",mass:95.96,cat:"transition",config:"[Kr] 4d⁵ 5s¹",year:"1778",phase:"solid"},
        {no:43,sym:"Tc",name:"Technetium",mass:98,cat:"transition",config:"[Kr] 4d⁵ 5s²",year:"1937",phase:"solid"},
        {no:44,sym:"Ru",name:"Ruthenium",mass:101.07,cat:"transition",config:"[Kr] 4d⁷ 5s¹",year:"1844",phase:"solid"},
        {no:45,sym:"Rh",name:"Rhodium",mass:102.91,cat:"transition",config:"[Kr] 4d⁸ 5s¹",year:"1803",phase:"solid"},
        {no:46,sym:"Pd",name:"Palladium",mass:106.42,cat:"transition",config:"[Kr] 4d¹⁰",year:"1803",phase:"solid"},
        {no:47,sym:"Ag",name:"Silver",mass:107.87,cat:"transition",config:"[Kr] 4d¹⁰ 5s¹",year:"Ancient",phase:"solid"},
        {no:48,sym:"Cd",name:"Cadmium",mass:112.41,cat:"transition",config:"[Kr] 4d¹⁰ 5s²",year:"1817",phase:"solid"},
        {no:49,sym:"In",name:"Indium",mass:114.82,cat:"post-transition",config:"[Kr] 4d¹⁰ 5s² 5p¹",year:"1863",phase:"solid"},
        {no:50,sym:"Sn",name:"Tin",mass:118.71,cat:"post-transition",config:"[Kr] 4d¹⁰ 5s² 5p²",year:"Ancient",phase:"solid"},
        {no:51,sym:"Sb",name:"Antimony",mass:121.76,cat:"metalloid",config:"[Kr] 4d¹⁰ 5s² 5p³",year:"Ancient",phase:"solid"},
        {no:52,sym:"Te",name:"Tellurium",mass:127.60,cat:"metalloid",config:"[Kr] 4d¹⁰ 5s² 5p⁴",year:"1782",phase:"solid"},
        {no:53,sym:"I",name:"Iodine",mass:126.90,cat:"halogen",config:"[Kr] 4d¹⁰ 5s² 5p⁵",year:"1811",phase:"solid"},
        {no:54,sym:"Xe",name:"Xenon",mass:131.29,cat:"noble-gas",config:"[Kr] 4d¹⁰ 5s² 5p⁶",year:"1898",phase:"gas"},
        {no:55,sym:"Cs",name:"Caesium",mass:132.91,cat:"alkali",config:"[Xe] 6s¹",year:"1860",phase:"solid"},
        {no:56,sym:"Ba",name:"Barium",mass:137.33,cat:"alkaline",config:"[Xe] 6s²",year:"1808",phase:"solid"},
        {no:57,sym:"La",name:"Lanthanum",mass:138.91,cat:"lanthanoid",config:"[Xe] 5d¹ 6s²",year:"1839",phase:"solid"},
        {no:58,sym:"Ce",name:"Cerium",mass:140.12,cat:"lanthanoid",config:"[Xe] 4f¹ 5d¹ 6s²",year:"1803",phase:"solid"},
        {no:59,sym:"Pr",name:"Praseodymium",mass:140.91,cat:"lanthanoid",config:"[Xe] 4f³ 6s²",year:"1885",phase:"solid"},
        {no:60,sym:"Nd",name:"Neodymium",mass:144.24,cat:"lanthanoid",config:"[Xe] 4f⁴ 6s²",year:"1885",phase:"solid"},
        {no:61,sym:"Pm",name:"Promethium",mass:145,cat:"lanthanoid",config:"[Xe] 4f⁵ 6s²",year:"1945",phase:"solid"},
        {no:62,sym:"Sm",name:"Samarium",mass:150.36,cat:"lanthanoid",config:"[Xe] 4f⁶ 6s²",year:"1879",phase:"solid"},
        {no:63,sym:"Eu",name:"Europium",mass:151.96,cat:"lanthanoid",config:"[Xe] 4f⁷ 6s²",year:"1901",phase:"solid"},
        {no:64,sym:"Gd",name:"Gadolinium",mass:157.25,cat:"lanthanoid",config:"[Xe] 4f⁷ 5d¹ 6s²",year:"1880",phase:"solid"},
        {no:65,sym:"Tb",name:"Terbium",mass:158.93,cat:"lanthanoid",config:"[Xe] 4f⁹ 6s²",year:"1843",phase:"solid"},
        {no:66,sym:"Dy",name:"Dysprosium",mass:162.50,cat:"lanthanoid",config:"[Xe] 4f¹⁰ 6s²",year:"1886",phase:"solid"},
        {no:67,sym:"Ho",name:"Holmium",mass:164.93,cat:"lanthanoid",config:"[Xe] 4f¹¹ 6s²",year:"1878",phase:"solid"},
        {no:68,sym:"Er",name:"Erbium",mass:167.26,cat:"lanthanoid",config:"[Xe] 4f¹² 6s²",year:"1843",phase:"solid"},
        {no:69,sym:"Tm",name:"Thulium",mass:168.93,cat:"lanthanoid",config:"[Xe] 4f¹³ 6s²",year:"1879",phase:"solid"},
        {no:70,sym:"Yb",name:"Ytterbium",mass:173.05,cat:"lanthanoid",config:"[Xe] 4f¹⁴ 6s²",year:"1878",phase:"solid"},
        {no:71,sym:"Lu",name:"Lutetium",mass:174.97,cat:"lanthanoid",config:"[Xe] 4f¹⁴ 5d¹ 6s²",year:"1907",phase:"solid"},
        {no:72,sym:"Hf",name:"Hafnium",mass:178.49,cat:"transition",config:"[Xe] 4f¹⁴ 5d² 6s²",year:"1923",phase:"solid"},
        {no:73,sym:"Ta",name:"Tantalum",mass:180.95,cat:"transition",config:"[Xe] 4f¹⁴ 5d³ 6s²",year:"1802",phase:"solid"},
        {no:74,sym:"W",name:"Tungsten",mass:183.84,cat:"transition",config:"[Xe] 4f¹⁴ 5d⁴ 6s²",year:"1783",phase:"solid"},
        {no:75,sym:"Re",name:"Rhenium",mass:186.21,cat:"transition",config:"[Xe] 4f¹⁴ 5d⁵ 6s²",year:"1925",phase:"solid"},
        {no:76,sym:"Os",name:"Osmium",mass:190.23,cat:"transition",config:"[Xe] 4f¹⁴ 5d⁶ 6s²",year:"1803",phase:"solid"},
        {no:77,sym:"Ir",name:"Iridium",mass:192.22,cat:"transition",config:"[Xe] 4f¹⁴ 5d⁷ 6s²",year:"1803",phase:"solid"},
        {no:78,sym:"Pt",name:"Platinum",mass:195.08,cat:"transition",config:"[Xe] 4f¹⁴ 5d⁹ 6s¹",year:"Ancient",phase:"solid"},
        {no:79,sym:"Au",name:"Gold",mass:196.97,cat:"transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s¹",year:"Ancient",phase:"solid"},
        {no:80,sym:"Hg",name:"Mercury",mass:200.59,cat:"transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s²",year:"Ancient",phase:"liquid"},
        {no:81,sym:"Tl",name:"Thallium",mass:204.38,cat:"post-transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p¹",year:"1861",phase:"solid"},
        {no:82,sym:"Pb",name:"Lead",mass:207.2,cat:"post-transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p²",year:"Ancient",phase:"solid"},
        {no:83,sym:"Bi",name:"Bismuth",mass:208.98,cat:"post-transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p³",year:"Ancient",phase:"solid"},
        {no:84,sym:"Po",name:"Polonium",mass:209,cat:"post-transition",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p⁴",year:"1898",phase:"solid"},
        {no:85,sym:"At",name:"Astatine",mass:210,cat:"halogen",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p⁵",year:"1940",phase:"solid"},
        {no:86,sym:"Rn",name:"Radon",mass:222,cat:"noble-gas",config:"[Xe] 4f¹⁴ 5d¹⁰ 6s² 6p⁶",year:"1900",phase:"gas"},
        {no:87,sym:"Fr",name:"Francium",mass:223,cat:"alkali",config:"[Rn] 7s¹",year:"1939",phase:"solid"},
        {no:88,sym:"Ra",name:"Radium",mass:226,cat:"alkaline",config:"[Rn] 7s²",year:"1898",phase:"solid"},
        {no:89,sym:"Ac",name:"Actinium",mass:227,cat:"actinoid",config:"[Rn] 6d¹ 7s²",year:"1899",phase:"solid"},
        {no:90,sym:"Th",name:"Thorium",mass:232.04,cat:"actinoid",config:"[Rn] 6d² 7s²",year:"1829",phase:"solid"},
        {no:91,sym:"Pa",name:"Protactinium",mass:231.04,cat:"actinoid",config:"[Rn] 5f² 6d¹ 7s²",year:"1913",phase:"solid"},
        {no:92,sym:"U",name:"Uranium",mass:238.03,cat:"actinoid",config:"[Rn] 5f³ 6d¹ 7s²",year:"1789",phase:"solid"},
        {no:93,sym:"Np",name:"Neptunium",mass:237,cat:"actinoid",config:"[Rn] 5f⁴ 6d¹ 7s²",year:"1940",phase:"solid"},
        {no:94,sym:"Pu",name:"Plutonium",mass:244,cat:"actinoid",config:"[Rn] 5f⁶ 7s²",year:"1940",phase:"solid"},
        {no:95,sym:"Am",name:"Americium",mass:243,cat:"actinoid",config:"[Rn] 5f⁷ 7s²",year:"1944",phase:"solid"},
        {no:96,sym:"Cm",name:"Curium",mass:247,cat:"actinoid",config:"[Rn] 5f⁷ 6d¹ 7s²",year:"1944",phase:"solid"},
        {no:97,sym:"Bk",name:"Berkelium",mass:247,cat:"actinoid",config:"[Rn] 5f⁹ 7s²",year:"1949",phase:"solid"},
        {no:98,sym:"Cf",name:"Californium",mass:251,cat:"actinoid",config:"[Rn] 5f¹⁰ 7s²",year:"1950",phase:"solid"},
        {no:99,sym:"Es",name:"Einsteinium",mass:252,cat:"actinoid",config:"[Rn] 5f¹¹ 7s²",year:"1952",phase:"solid"},
        {no:100,sym:"Fm",name:"Fermium",mass:257,cat:"actinoid",config:"[Rn] 5f¹² 7s²",year:"1952",phase:"solid"},
        {no:101,sym:"Md",name:"Mendelevium",mass:258,cat:"actinoid",config:"[Rn] 5f¹³ 7s²",year:"1955",phase:"solid"},
        {no:102,sym:"No",name:"Nobelium",mass:259,cat:"actinoid",config:"[Rn] 5f¹⁴ 7s²",year:"1958",phase:"solid"},
        {no:103,sym:"Lr",name:"Lawrencium",mass:262,cat:"actinoid",config:"[Rn] 5f¹⁴ 7s² 7p¹",year:"1961",phase:"solid"},
        {no:104,sym:"Rf",name:"Rutherfordium",mass:267,cat:"transition",config:"[Rn] 5f¹⁴ 6d² 7s²",year:"1964",phase:"solid"},
        {no:105,sym:"Db",name:"Dubnium",mass:270,cat:"transition",config:"[Rn] 5f¹⁴ 6d³ 7s²",year:"1967",phase:"solid"},
        {no:106,sym:"Sg",name:"Seaborgium",mass:271,cat:"transition",config:"[Rn] 5f¹⁴ 6d⁴ 7s²",year:"1974",phase:"solid"},
        {no:107,sym:"Bh",name:"Bohrium",mass:270,cat:"transition",config:"[Rn] 5f¹⁴ 6d⁵ 7s²",year:"1976",phase:"solid"},
        {no:108,sym:"Hs",name:"Hassium",mass:277,cat:"transition",config:"[Rn] 5f¹⁴ 6d⁶ 7s²",year:"1984",phase:"solid"},
        {no:109,sym:"Mt",name:"Meitnerium",mass:276,cat:"transition",config:"[Rn] 5f¹⁴ 6d⁷ 7s²",year:"1982",phase:"solid"},
        {no:110,sym:"Ds",name:"Darmstadtium",mass:281,cat:"transition",config:"[Rn] 5f¹⁴ 6d⁹ 7s¹",year:"1994",phase:"solid"},
        {no:111,sym:"Rg",name:"Roentgenium",mass:280,cat:"transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s¹",year:"1994",phase:"solid"},
        {no:112,sym:"Cn",name:"Copernicium",mass:285,cat:"transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s²",year:"1996",phase:"solid"},
        {no:113,sym:"Nh",name:"Nihonium",mass:286,cat:"post-transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p¹",year:"2004",phase:"solid"},
        {no:114,sym:"Fl",name:"Flerovium",mass:289,cat:"post-transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p²",year:"1999",phase:"solid"},
        {no:115,sym:"Mc",name:"Moscovium",mass:290,cat:"post-transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p³",year:"2003",phase:"solid"},
        {no:116,sym:"Lv",name:"Livermorium",mass:293,cat:"post-transition",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p⁴",year:"2000",phase:"solid"},
        {no:117,sym:"Ts",name:"Tennessine",mass:294,cat:"halogen",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p⁵",year:"2010",phase:"solid"},
        {no:118,sym:"Og",name:"Oganesson",mass:294,cat:"noble-gas",config:"[Rn] 5f¹⁴ 6d¹⁰ 7s² 7p⁶",year:"2006",phase:"gas"}
    ];

    const mainTableLayout = [
        [1,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,2],
        [3,4,null,null,null,null,null,null,null,null,null,null,5,6,7,8,9,10],
        [11,12,null,null,null,null,null,null,null,null,null,null,13,14,15,16,17,18],
        [19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36],
        [37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54],
        [55,56,'57-71',72,73,74,75,76,77,78,79,80,81,82,83,84,85,86],
        [87,88,'89-103',104,105,106,107,108,109,110,111,112,113,114,115,116,117,118]
    ];

    const table = document.getElementById('ptable');
    const lanthanoids = document.getElementById('lanthanoids');
    const actinoids = document.getElementById('actinoids');

    function createElementCell(el) {
        const cell = document.createElement('div');
        cell.className = `element ${el.cat}`;
        cell.innerHTML = `
            <div class="atomic-number">${el.no}</div>
            <div class="symbol">${el.sym}</div>
            <div class="name">${el.name}</div>
            <div class="mass">${el.mass}</div>
        `;
        cell.addEventListener('click', () => showModal(el));
        return cell;
    }

    function createPlaceholder(text) {
        const cell = document.createElement('div');
        cell.className = 'element placeholder';
        cell.innerHTML = `<div class="symbol">${text}</div>`;
        return cell;
    }

    function createEmpty() {
        const cell = document.createElement('div');
        cell.className = 'empty';
        return cell;
    }

    function showModal(el) {
        document.getElementById('modal-atomic').textContent = el.no;
        document.getElementById('modal-symbol').textContent = el.sym;
        document.getElementById('modal-name').textContent = el.name;
        document.getElementById('modal-mass').textContent = el.mass;
        document.getElementById('modal-config').textContent = el.config;
        document.getElementById('modal-category').textContent = el.cat.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        document.getElementById('modal-year').textContent = el.year;
        document.getElementById('modal-phase').textContent = el.phase.charAt(0).toUpperCase() + el.phase.slice(1);
        
        const modal = new bootstrap.Modal(document.getElementById('elementModal'));
        modal.show();
    }

    mainTableLayout.forEach(row => {
        row.forEach(cellData => {
            if (cellData === null) {
                table.appendChild(createEmpty());
            } else if (typeof cellData === 'string') {
                table.appendChild(createPlaceholder(cellData));
            } else {
                const el = elements.find(e => e.no === cellData);
                if (el) table.appendChild(createElementCell(el));
            }
        });
    });

    for (let i = 57; i <= 71; i++) {
        const el = elements.find(e => e.no === i);
        if (el) lanthanoids.appendChild(createElementCell(el));
    }

    for (let i = 89; i <= 103; i++) {
        const el = elements.find(e => e.no === i);
        if (el) actinoids.appendChild(createElementCell(el));
    }
</script>
</body>
</html>