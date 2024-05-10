 #####  ####### ####### #    # ### #######  #####  #     #  #####     ######  ######  ### #     #    #    ####### ####### ######  
#     # #     # #     # #   #   #  #       #     # ##   ## #     #    #     # #     #  #  #     #   # #      #    #       #     # 
#       #     # #     # #  #    #  #       #       # # # # #          #     # #     #  #  #     #  #   #     #    #       #     # 
#       #     # #     # ###     #  #####   #       #  #  #  #####     ######  ######   #  #     # #     #    #    #####   #     # 
#       #     # #     # #  #    #  #       #       #     #       #    #       #   #    #   #   #  #######    #    #       #     # 
#     # #     # #     # #   #   #  #       #     # #     # #     #    #       #    #   #    # #   #     #    #    #       #     # 
 #####  ####### ####### #    # ### #######  #####  #     #  #####     #       #     # ###    #    #     #    #    ####### ######  
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

    public function render($template, $variables = []) {
        $content = file_get_contents($template);

        // Process include statements
        $content = preg_replace_callback('/{{\s*include\s*\'(.*?)\'\s*}}/', function($matches) {
            $includeFile = $matches[1];

            if (defined($includeFile)) {
                $includeFile = constant($includeFile);
            }

            $includeFile = rtrim($_SERVER['DOCUMENT_ROOT'] . '/template', '/') . '/' . $includeFile;

            if (file_exists($includeFile)) {
                return $this->includeFile($includeFile);
            } else {
                echo "File not found: $includeFile<br>";
                return '';
            }
        }, $content);

        // Process if statements
        $content = preg_replace_callback('/{{\s*if\s*(.*?)\s*}}(.*?)(?:{{\s*else\s*}}(.*?))?{{\s*endif\s*}}/s', function($matches) {
            $condition = $matches[1];
            $ifContent = $matches[2];
            $elseContent = isset($matches[3]) ? $matches[3] : '';

            return '<?php if (' . $this->parseCondition($condition) . '): ?>' . $this->parseVariables($ifContent) . '<?php else: ?>' . $this->parseVariables($elseContent) . '<?php endif; ?>';
        }, $content);

        // Process if-else statements with 'and'
        $content = preg_replace_callback('/{{\s*if\s*(.*?)\s*and\s*(.*?)\s*}}(.*?)(?:{{\s*else\s*}}(.*?))?{{\s*endif\s*}}/s', function($matches) {
            $condition1 = $matches[1];
            $condition2 = $matches[2];
            $ifContent = $matches[3];
            $elseContent = isset($matches[4]) ? $matches[4] : '';

            return '<?php if (' . $this->parseCondition($condition1) . ' && ' . $this->parseCondition($condition2) . '): ?>' . $this->parseVariables($ifContent) . '<?php else: ?>' . $this->parseVariables($elseContent) . '<?php endif; ?>';
        }, $content);

        // Process debug_echo statements
        $content = preg_replace_callback('/{{\s*debug_echo\s*(.*?)\s*}}/', function($matches) {
            $variable = $matches[1];
            return '<?php $this->debugEcho("' . $variable . '"); ?>';
        }, $content);

        // Process foreach loops
        $content = preg_replace_callback('/{{\s*foreach\s*(.*?)\s*as\s*(.*?)\s*}}(.*?)(?:{{\s*endforeach\s*}})/s', function($matches) use ($variables) {
            $variable = trim($matches[1]);
            $loopVariable = trim($matches[2]);
            $loopContent = trim($matches[3]);
            
            // Check if the variable exists in the provided variables or in the data of the TemplateEngine
            $loopData = isset($variables[$variable]) ? $variables[$variable] : $this->getVariable($variable);

            // Ensure $loopData is an array
            if (!is_array($loopData)) {
                return ''; // Return an empty string if $loopData is not an array
            }

            // Initialize the output string
            $output = '';

            // Iterate over each element in the loop data
            foreach ($loopData as $loopKey => $loopValue) {
                // Replace occurrences of the loop key and value in the loop content
                $loopContentWithReplacements = str_replace('{{ ' . $loopVariable . ' }}', $loopValue, $loopContent);
                
                // Append the modified loop content to the output string
                $output .= $loopContentWithReplacements;
            }

            // Return the final output
            return $output;
        }, $content);


        // Parse variables
        $content = $this->parseVariables($content);

        // Evaluate the content
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
        return $variable;
    }

    private function debugEcho($variable) {
        if (isset($this->data[$variable])) {
            echo $variable . ': ';
            var_dump($this->data[$variable]);
        } elseif (isset($this->headerData[$variable])) {
            echo $variable . ': ';
            var_dump($this->headerData[$variable]);
        } else {
            echo $variable . ': Variable not found';
        }
    }
}
?>

