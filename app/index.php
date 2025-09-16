<?php

session_start();
$result = null;
$error = null;


if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

function validator($number)
{
    if (is_numeric($number) || is_float($number)) {
        return true;
    }
    return 'Не число';
}

function operatorValidator($operator)
{
    if (!in_array($operator, ['+', '-', '/', '*', '%', '**'])) {
        return 'Не оператор';
    }
    return true;

}

function plus(float $a, float $b): float
{
    return bcadd($a, $b, 10);
}

function minus(float $a, float $b): float
{
    return bcsub($a, $b, 10);
}

function divide(float $a, float $b)
{
    if ($b !== 0) {
        return bcdiv($a, $b, 10);
    }
    return false;
}

function times(float $a, float $b): float
{
    return bcmul($a, $b, 10);
}

function modulus(float $a, float $b)
{
    if ($b !== 0) {
        return bcmod($a, $b, 10);
    }
    return false;
}

function exponentiation(float $a, float $b): float
{
    return bcpow($a, $b, 10);
}

function checkOperation(string $operation): string|bool
{
    return match ($operation) {
        '+' => 'plus',
        '-' => 'minus',
        '/' => 'divide',
        '*' => 'times',
        '%' => 'modulus',
        '**' => 'exponentiation',
        default => false,
    };
}

function normalizeResult(string $result): string
{
    return rtrim(rtrim($result, '0'), '.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        $data = $_POST;

        if (($check = validator($data['first'])) !== true) {
            throw new Exception($check);
        }

        if (($check = validator($data['second'])) !== true) {
            throw new Exception($check);
        }

        if (($check = operatorValidator($data['operator'])) !== true) {
            throw new Exception($check);
        }

        $operation = checkOperation($data['operator']);

        if (!$operation || !function_exists($operation)) {
            throw new Exception("Невідома операція");
        }

        $resultRaw = $operation((string)$data['first'], $data['second']);
        if ($resultRaw === false) {
            throw new Exception("Помилка при обчисленні (можливо ділення на нуль)");
        }

        $result = normalizeResult($resultRaw);

        $_SESSION['history'][] = [
            'first' => $data['first'],
            'operator' => $data['operator'],
            'second' => $data['second'],
            'result' => $result,
            'time' => date('Y-m-d H:i:s'),
        ];

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(-45deg, #0d1b2a, #1b263b, #3a0ca3, #e63946);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: #fff;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .calculator {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }

        .btn-custom {
            background: #4361ee;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 0.75rem;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: #3a0ca3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }

        ul.history {
            list-style: none;
            padding: 0;
            margin: 1rem 0 0;
            max-height: 200px; /* обмежуємо висоту */
            overflow-y: auto;  /* скрол */
        }

        ul.history li {
            background: rgba(255, 255, 255, 0.07);
            margin-bottom: 0.5rem;
            padding: 0.5rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="calculator">
    <h1>Calculator</h1>
    <form method="post" class="mb-3">
        <div class="mb-3">
            <label for="first" class="form-label">First</label>
            <input type="number" name="first" id="first" step="any" class="form-control">
        </div>

        <div class="mb-3">
            <label for="second" class="form-label">Second</label>
            <input type="number" name="second" id="second" step="any" class="form-control">
        </div>

        <div class="mb-3">
            <label for="operator" class="form-label">Operator</label>
            <select name="operator" id="operator" class="form-select">
                <option value="+">+</option>
                <option value="-">-</option>
                <option value="/">/</option>
                <option value="*">*</option>
                <option value="%">%</option>
                <option value="**">**</option>
            </select>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-custom">Calculate</button>
        </div>
    </form>

    <?php if ($result !== null): ?>
        <p><strong>Результат:</strong> <?= htmlspecialchars($result) ?></p>
    <?php elseif ($error !== null): ?>
        <p style="color: #ff6b6b;"><strong>Помилка:</strong> <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['history'])): ?>
        <h4>History</h4>
        <ul class="history">
            <?php foreach (array_reverse($_SESSION['history']) as $item): ?>
                <li>
                    <?= htmlspecialchars($item['first']) ?>
                    <?= htmlspecialchars($item['operator']) ?>
                    <?= htmlspecialchars($item['second']) ?> =
                    <?= htmlspecialchars($item['result']) ?>
                    (<?= htmlspecialchars($item['time']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

</body>
</html>


