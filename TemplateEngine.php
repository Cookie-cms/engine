<?php
class TemplateEngine {
    private $data = [];
    private $headerData = [];

    public function assign($variable, $value) {
        $this->data[$variable] = $value;
    }

    public function assignHeader($variable, $value) {
        $this->headerData[$variable] = $value;
    }

    public function render($template) {
        $content = file_get_contents($template);

        // Replace {{ include 'filename' }} with file contents
        $content = preg_replace_callback('/{{\s*include\s*\'(.*?)\'\s*}}/', function($matches) {
            $includeFile = $matches[1];
            $includeContent = file_get_contents($includeFile);
            return $this->parseVariables($includeContent);
        }, $content);

        // Replace {{ if ... }} and {{ else }} with PHP control structures
        $content = preg_replace_callback('/{{\s*if\s*(.*?)\s*}}(.*?)(?:{{\s*else\s*}}(.*?))?{{\s*endif\s*}}/s', function($matches) {
            $condition = $matches[1];
            $ifContent = $matches[2];
            $elseContent = isset($matches[3]) ? $matches[3] : '';

            return '<?php if (' . $this->parseCondition($condition) . '): ?>' . $this->parseVariables($ifContent) . '<?php else: ?>' . $this->parseVariables($elseContent) . '<?php endif; ?>';
        }, $content);

        // Replace variables
        $content = $this->parseVariables($content);

        ob_start();
        eval(' ?>' . $content . '<?php ');
        return ob_get_clean();
    }

    private function parseCondition($condition) {
        return (isset($this->data[$condition]) && $this->data[$condition]) ? 'true' : 'false';
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
