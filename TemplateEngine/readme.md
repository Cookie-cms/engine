## TemplateEngine PHP

The TemplateEngine is a lightweight PHP template engine designed to simplify the separation of HTML templates and PHP logic. It provides support for variable substitution, conditional statements, file inclusion, and more.

### Usage

#### Initialization

```php
require_once 'TemplateEngine.php';

// Create an instance of the TemplateEngine class
$templateEngine = new TemplateEngine();
```

#### Assigning Variables

```php
// Assign variables for use in the template
$templateEngine->assign('variableName', 'variableValue');
```

#### Including Header Variables

```php
// Assign header variables for use in included files
$templateEngine->assignHeader('headerVariable', 'headerValue');
```

#### Including Files

Use the `{{ include 'filename' }}` syntax to include files within your template.

```php
// Render the template
$template = $templateEngine->render('template.tpl');
echo $template;
```

In `template.tpl`:

```tpl
<html>
<head>
    {{ include 'header.php' }}
</head>
<body>
    <h1>Welcome, {{ username }}</h1>
</body>
</html>
```

In `header.php`:

```php
<title>{{ pageTitle }}</title>
```

#### Conditional Statements

Use `{{ if condition }} ... {{ else }} ... {{ endif }}` syntax for conditional statements.

```html
{{ if isLoggedIn }}
    <p>Welcome, {{ username }}!</p>
{{ else }}
    <p>Please log in.</p>
{{ endif }}
```

#### Rendering

```php
// Render the template
$output = $templateEngine->render('template.php');
echo $output;
```

### Note

Ensure that all included files and template files are accessible from the directory where the PHP script is executed.

This template engine is intended for educational purposes or small projects. For larger projects, consider using established templating engines like Twig.
