<?php
/*  calculator.php  –  Scientific Calculator for ChemEase  */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scientific Calculator – ChemEase</title>

    <!-- Bootstrap 5 + Font Awesome (same versions used in the main layout) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            background: var(--light-gray);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .calc-container {
            max-width: 420px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,.12);
        }

        .calc-header {
            background: var(--primary-blue);
            color: #fff;
            padding: 1rem;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .calc-display {
            position: relative;
            background: var(--input-bg);
            padding: 1rem;
            font-size: 2rem;
            text-align: right;
            min-height: 70px;
            word-break: break-all;
            border-bottom: 1px solid #ddd;
        }

        .calc-display input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: inherit;
            text-align: inherit;
            color: var(--dark-text);
            outline: none;
        }

        .calc-buttons {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1px;
            background: #ddd;
        }

        .calc-btn {
            background: #fff;
            border: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 1rem;
            transition: all .15s ease;
            user-select: none;
            cursor: pointer;
        }

        .calc-btn:hover {
            background: var(--light-blue);
        }

        .calc-btn:active {
            background: var(--primary-blue);
            color: #fff;
        }

        /* Special button colours */
        .calc-btn.op   { background: #f8d7da; }   /* operators */
        .calc-btn.op:hover   { background: #f5c6cb; }
        .calc-btn.eq   { background: var(--primary-blue); color:#fff; }
        .calc-btn.eq:hover { background: #138496; }

        .calc-btn.span-2 {
            grid-column: span 2;
        }

        /* Responsive tweaks */
        @media (max-width: 480px) {
            .calc-container { margin: 1rem; }
            .calc-display { font-size: 1.6rem; }
            .calc-btn { font-size: 1rem; padding: .8rem; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="calc-container">
        <div class="calc-header">
            <i class="fas fa-calculator me-2"></i>Scientific Calculator
        </div>

        <div class="calc-display">
            <input type="text" id="result" value="0" readonly>
        </div>

        <div class="calc-buttons">
            <!-- Row 1 -->
            <button class="calc-btn" data-action="clear">C</button>
            <button class="calc-btn" data-action="backspace"><i class="fas fa-backspace"></i></button>
            <button class="calc-btn op" data-action="memory-recall">MR</button>
            <button class="calc-btn op" data-action="memory-clear">MC</button>
            <button class="calc-btn op" data-action="memory-add">M+</button>

            <!-- Row 2 -->
            <button class="calc-btn" data-fn="sin">sin</button>
            <button class="calc-btn" data-fn="cos">cos</button>
            <button class="calc-btn" data-fn="tan">tan</button>
            <button class="calc-btn" data-fn="log">log</button>
            <button class="calc-btn" data-fn="ln">ln</button>

            <!-- Row 3 -->
            <button class="calc-btn" data-fn="sqrt">√</button>
            <button class="calc-btn" data-fn="square">x²</button>
            <button class="calc-btn" data-fn="power">x^y</button>
            <button class="calc-btn" data-fn="factorial">n!</button>
            <button class="calc-btn op" data-action="percent">%</button>

            <!-- Row 4 -->
            <button class="calc-btn" data-val="7">7</button>
            <button class="calc-btn" data-val="8">8</button>
            <button class="calc-btn" data-val="9">9</button>
            <button class="calc-btn op" data-val="/">÷</button>
            <button class="calc-btn op" data-val="*">×</button>

            <!-- Row 5 -->
            <button class="calc-btn" data-val="4">4</button>
            <button class="calc-btn" data-val="5">5</button>
            <button class="calc-btn" data-val="6">6</button>
            <button class="calc-btn op" data-val="-">−</button>
            <button class="calc-btn op" data-val="+">+</button>

            <!-- Row 6 -->
            <button class="calc-btn" data-val="1">1</button>
            <button class="calc-btn" data-val="2">2</button>
            <button class="calc-btn" data-val="3">3</button>
            <button class="calc-btn span-2 eq" data-action="equals">=</button>

            <!-- Row 7 -->
            <button class="calc-btn span-2" data-val="0">0</button>
            <button class="calc-btn" data-val=".">.</button>
            <button class="calc-btn op" data-val="(">(</button>
            <button class="calc-btn op" data-val=")">)</button>
        </div>
    </div>
</div>

<script>
    /* --------------------------------------------------------------
       Scientific Calculator – pure JavaScript
       -------------------------------------------------------------- */
    const display = document.getElementById('result');
    let memory = 0;
    let shouldReset = false;

    // Helper – format number (avoid scientific notation for small numbers)
    function format(num) {
        if (Math.abs(num) < 1e-6 && num !== 0) return num.toExponential(4);
        return Number(num.toPrecision(12)).toString();
    }

    // Trigonometric functions (degree / radian toggle)
    let degMode = true; // true = degrees, false = radians
    function trig(fn, val) {
        const rad = degMode ? val * Math.PI / 180 : val;
        return Math[fn](rad);
    }

    // Factorial
    function factorial(n) {
        if (n === 0 || n === 1) return 1;
        if (n < 0 || !Number.isInteger(n)) return NaN;
        let res = 1;
        for (let i = 2; i <= n; i++) res *= i;
        return res;
    }

    // Button click handler
    document.querySelectorAll('.calc-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.val;
            const fn  = btn.dataset.fn;
            const op  = btn.dataset.action;

            if (shouldReset) {
                display.value = '0';
                shouldReset = false;
            }

            // ---------- Numbers & decimal ----------
            if (val !== undefined) {
                if (display.value === '0' && val !== '.') display.value = '';
                display.value += val;
                return;
            }

            // ---------- Functions ----------
            if (fn) {
                const current = parseFloat(display.value) || 0;
                let result = 0;

                switch (fn) {
                    case 'sin':  result = trig('sin', current); break;
                    case 'cos':  result = trig('cos', current); break;
                    case 'tan':  result = trig('tan', current); break;
                    case 'log':  result = Math.log10(current); break;
                    case 'ln':   result = Math.log(current); break;
                    case 'sqrt': result = Math.sqrt(current); break;
                    case 'square': result = current * current; break;
                    case 'power':
                        // x^y – take next number as exponent
                        display.value = current + '^';
                        return;
                    case 'factorial':
                        result = factorial(current);
                        break;
                }
                display.value = isNaN(result) ? 'Error' : format(result);
                shouldReset = true;
                return;
            }

            // ---------- Actions ----------
            if (op) {
                switch (op) {
                    case 'clear':
                        display.value = '0';
                        break;
                    case 'backspace':
                        display.value = display.value.slice(0, -1) || '0';
                        break;
                    case 'memory-clear':
                        memory = 0;
                        break;
                    case 'memory-recall':
                        display.value = format(memory);
                        shouldReset = true;
                        break;
                    case 'memory-add':
                        memory += parseFloat(display.value) || 0;
                        shouldReset = true;
                        break;
                    case 'percent':
                        display.value = (parseFloat(display.value) / 100).toString();
                        break;
                    case 'equals':
                        try {
                            // Simple replace for display-friendly symbols
                            let expr = display.value
                                .replace(/×/g, '*')
                                .replace(/÷/g, '/')
                                .replace(/−/g, '-')
                                .replace(/\^/g, '**');

                            // eslint-disable-next-line no-eval
                            const evalResult = eval(expr);
                            display.value = isNaN(evalResult) ? 'Error' : format(evalResult);
                        } catch (e) {
                            display.value = 'Error';
                        }
                        shouldReset = true;
                        break;
                }
                return;
            }
        });
    });

    // Optional: toggle DEG / RAD (you can add a button later)
    // For now it defaults to degrees (common for school calculators)
</script>

</body>
</html>