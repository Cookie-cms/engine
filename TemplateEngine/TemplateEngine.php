<?php

class TemplateEngine {
    private $data = [];
    private $headerData = [];
    private $includedFiles = [];

    public function assign($variable, $value) {
        $this->data[$variable] = $value;
    }

    public function assignHeader($variable, $value) {
        $this->headerData[$variable] = $value;
    }

    public function includeFile($file, $variables = []) {
        $this->includedFiles[] = $file;

        foreach ($this->data as $key => $value) {
            ${$key} = $value;
        }

        foreach ($variables as $key => $value) {
            ${$key} = $value;
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function render($template) {
        $content = file_get_contents($template);

        $content = preg_replace_callback('/{{\s*include\s*\'(.*?)\'\s*}}/', function($matches) {
            $includeFile = $matches[1];

            if (defined($includeFile)) {
                $includeFile = constant($includeFile);
            }

            echo "Including file: $includeFile<br>";

            // Construct an absolute path using __TD__
            $includeFile = rtrim($_SERVER['DOCUMENT_ROOT'] . '/template' , '/') . '/' . $includeFile;

            if (file_exists($includeFile)) {
                return $this->includeFile($includeFile);
            } else {
                echo "File not found: $includeFile<br>";
                return '';
            }
        }, $content);

        // Replace {{ if ... }} and {{ else }} with PHP control structures
        $content = preg_replace_callback('/{{\s*if\s*(.*?)\s*}}(.*?)(?:{{\s*else\s*}}(.*?))?{{\s*endif\s*}}/s', function($matches) {
            $condition = $matches[1];
            $ifContent = $matches[2];
            $elseContent = isset($matches[3]) ? $matches[3] : '';

            return '<?php if (' . $this->parseCondition($condition) . '): ?>' . $this->parseVariables($ifContent) . '<?php else: ?>' . $this->parseVariables($elseContent) . '<?php endif; ?>';
        }, $content);


        $content = $this->parseVariables($content);

        ob_start();
        eval(' ?>' . $content . '<?php ');
        return ob_get_clean();
    }

    private function parseCondition($condition) {
        $condition = preg_replace('/\b(==|!=|>=|<=)\b/', '\'$1\'', $condition);
        $condition = preg_replace('/\b=\b/', '==', $condition);  
        $result = eval("return $condition;");
        return $result ? 'true' : 'false';
    }

    private function parseVariables($content) {
        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function($matches) {
            return '<?php echo $this->getVariable("' . $matches[1] . '"); ?>';
        }, $content);
    }

    private function getVariable($variable) {
        if (isset($this->data[$variable])) {
            return $this->data[$variable];
        } elseif (isset($this->headerData[$variable])) {
            return $this->headerData[$variable];
        }
        return '';
    }
}
?>
