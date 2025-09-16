<?php

session_start();

if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

$error = null;
$result = null;
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
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Culc</title>
</head>
<body>

<form method="post">

    <div>
        <label for="first">First</label>
        <input type="number" name="first" step="any">
    </div>

    <div>

        <label for="second">Second</label>
        <input type="number" name="second" step="any">
    </div>
    <div>
        <select name="operator" id="" name="operator">
            <option value="+">+</option>
            <option value="-">-</option>
            <option value="/">/</option>
            <option value="*">*</option>
            <option value="%">%</option>
            <option value="**">**</option>
        </select>
    </div>
    <div>
        <button type="submit">culc</button>
    </div>
</form>

<?php if ($result !== null): ?>
    <p><strong>Результат:</strong> <?= htmlspecialchars($result) ?></p>
<?php elseif ($error !== null): ?>
    <p style="color: red;"><strong>Помилка:</strong> <?= htmlspecialchars($error) ?></p>
<?php endif; ?>



<?php if (!empty($_SESSION['history'])): ?>
    <h3>Історія обчислень:</h3>
    <ul>
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

</body>
</html>

