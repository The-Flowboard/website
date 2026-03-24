<?php
/**
 * Input Validation Framework
 *
 * Centralized validation utility for all forms and user inputs
 * Provides consistent validation rules, error messages, and sanitization
 *
 * @package JMC Website
 * @version 1.0
 * @created January 31, 2026
 */

class InputValidator {

    /**
     * Validation errors
     * @var array
     */
    private $errors = [];

    /**
     * Validated and sanitized data
     * @var array
     */
    private $validated = [];

    /**
     * Validation rules
     * @var array
     */
    private $rules = [];

    /**
     * Custom error messages
     * @var array
     */
    private $customMessages = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->errors = [];
        $this->validated = [];
        $this->rules = [];
        $this->customMessages = [];
    }

    /**
     * Set validation rules
     *
     * @param string $field Field name
     * @param string|array $rules Validation rules (pipe-separated or array)
     * @param string $label Optional field label for error messages
     * @return self
     */
    public function setRule($field, $rules, $label = null) {
        $this->rules[$field] = [
            'rules' => is_array($rules) ? $rules : explode('|', $rules),
            'label' => $label ?? ucfirst(str_replace('_', ' ', $field))
        ];

        return $this;
    }

    /**
     * Set custom error message for a field/rule combination
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param string $message Custom error message
     * @return self
     */
    public function setMessage($field, $rule, $message) {
        if (!isset($this->customMessages[$field])) {
            $this->customMessages[$field] = [];
        }

        $this->customMessages[$field][$rule] = $message;

        return $this;
    }

    /**
     * Validate input data against defined rules
     *
     * @param array $data Input data to validate
     * @return bool True if validation passes, false otherwise
     */
    public function validate($data) {
        $this->errors = [];
        $this->validated = [];

        foreach ($this->rules as $field => $config) {
            $value = $data[$field] ?? null;
            $rules = $config['rules'];
            $label = $config['label'];

            foreach ($rules as $rule) {
                // Parse rule and parameters
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                // Execute validation rule
                $result = $this->executeRule($field, $value, $rule, $params, $label);

                // If validation fails, store error and stop checking this field
                if ($result !== true) {
                    $this->errors[$field] = $result;
                    break;
                }
            }

            // If no errors, add sanitized value to validated array
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $this->sanitize($value, $rules);
            }
        }

        return empty($this->errors);
    }

    /**
     * Execute a validation rule
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @param string $label Field label
     * @return true|string True if valid, error message if invalid
     */
    private function executeRule($field, $value, $rule, $params, $label) {
        // Check for custom error message
        $customMessage = $this->customMessages[$field][$rule] ?? null;

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return $customMessage ?? "{$label} is required.";
                }
                return true;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $customMessage ?? "{$label} must be a valid email address.";
                }
                return true;

            case 'min':
                $min = $params[0] ?? 0;
                if (!empty($value) && strlen($value) < $min) {
                    return $customMessage ?? "{$label} must be at least {$min} characters.";
                }
                return true;

            case 'max':
                $max = $params[0] ?? 0;
                if (!empty($value) && strlen($value) > $max) {
                    return $customMessage ?? "{$label} must not exceed {$max} characters.";
                }
                return true;

            case 'min_value':
                $min = $params[0] ?? 0;
                if (!empty($value) && (int)$value < $min) {
                    return $customMessage ?? "{$label} must be at least {$min}.";
                }
                return true;

            case 'max_value':
                $max = $params[0] ?? 0;
                if (!empty($value) && (int)$value > $max) {
                    return $customMessage ?? "{$label} must not exceed {$max}.";
                }
                return true;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return $customMessage ?? "{$label} must be a number.";
                }
                return true;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return $customMessage ?? "{$label} must be an integer.";
                }
                return true;

            case 'alpha':
                if (!empty($value) && !preg_match('/^[a-zA-Z]+$/', $value)) {
                    return $customMessage ?? "{$label} must contain only letters.";
                }
                return true;

            case 'alpha_numeric':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    return $customMessage ?? "{$label} must contain only letters and numbers.";
                }
                return true;

            case 'alpha_dash':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    return $customMessage ?? "{$label} must contain only letters, numbers, dashes, and underscores.";
                }
                return true;

            case 'phone':
                if (!empty($value) && !preg_match('/^[\d\s\+\(\)\-\.]+$/', $value)) {
                    return $customMessage ?? "{$label} must be a valid phone number.";
                }
                return true;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $customMessage ?? "{$label} must be a valid URL.";
                }
                return true;

            case 'in':
                if (!empty($value) && !in_array($value, $params)) {
                    $options = implode(', ', $params);
                    return $customMessage ?? "{$label} must be one of: {$options}.";
                }
                return true;

            case 'regex':
                $pattern = $params[0] ?? '';
                if (!empty($value) && !preg_match($pattern, $value)) {
                    return $customMessage ?? "{$label} format is invalid.";
                }
                return true;

            case 'matches':
                $matchField = $params[0] ?? '';
                $matchValue = $_POST[$matchField] ?? $_GET[$matchField] ?? null;
                if (!empty($value) && $value !== $matchValue) {
                    $matchLabel = $this->rules[$matchField]['label'] ?? $matchField;
                    return $customMessage ?? "{$label} must match {$matchLabel}.";
                }
                return true;

            case 'unique':
                // Requires database connection
                // Format: unique:table,column,except_id
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;
                $exceptId = $params[2] ?? null;

                if (!empty($value) && !empty($table)) {
                    global $conn;
                    if ($conn) {
                        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
                        $types = 's';
                        $bindParams = [$value];

                        if ($exceptId) {
                            $sql .= " AND id != ?";
                            $types .= 'i';
                            $bindParams[] = $exceptId;
                        }

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$bindParams);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();

                        if ($result['count'] > 0) {
                            return $customMessage ?? "{$label} already exists.";
                        }
                    }
                }
                return true;

            case 'exists':
                // Requires database connection
                // Format: exists:table,column
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;

                if (!empty($value) && !empty($table)) {
                    global $conn;
                    if ($conn) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
                        $stmt->bind_param('s', $value);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();

                        if ($result['count'] === 0) {
                            return $customMessage ?? "{$label} does not exist.";
                        }
                    }
                }
                return true;

            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    return $customMessage ?? "{$label} must be a valid date.";
                }
                return true;

            case 'before':
                $beforeDate = $params[0] ?? 'now';
                if (!empty($value) && strtotime($value) >= strtotime($beforeDate)) {
                    return $customMessage ?? "{$label} must be before {$beforeDate}.";
                }
                return true;

            case 'after':
                $afterDate = $params[0] ?? 'now';
                if (!empty($value) && strtotime($value) <= strtotime($afterDate)) {
                    return $customMessage ?? "{$label} must be after {$afterDate}.";
                }
                return true;

            default:
                // Unknown rule - skip
                return true;
        }
    }

    /**
     * Sanitize value based on rules
     *
     * @param mixed $value Value to sanitize
     * @param array $rules Validation rules
     * @return mixed Sanitized value
     */
    private function sanitize($value, $rules) {
        if (empty($value)) {
            return $value;
        }

        // Always trim strings
        if (is_string($value)) {
            $value = trim($value);
        }

        // Additional sanitization based on rules
        if (in_array('email', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        if (in_array('url', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_URL);
        }

        if (in_array('integer', $rules) || in_array('numeric', $rules)) {
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        }

        // Escape HTML for safety (except for content fields that need HTML)
        if (!in_array('html', $rules)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Get validation errors
     *
     * @return array Errors array with field => error message
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get error for a specific field
     *
     * @param string $field Field name
     * @return string|null Error message or null if no error
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }

    /**
     * Check if validation has errors
     *
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get validated and sanitized data
     *
     * @return array
     */
    public function getValidated() {
        return $this->validated;
    }

    /**
     * Get a single validated value
     *
     * @param string $field Field name
     * @param mixed $default Default value if not set
     * @return mixed
     */
    public function get($field, $default = null) {
        return $this->validated[$field] ?? $default;
    }

    /**
     * Reset validator state
     *
     * @return self
     */
    public function reset() {
        $this->errors = [];
        $this->validated = [];
        $this->rules = [];
        $this->customMessages = [];

        return $this;
    }

    /**
     * Quick validation helper
     *
     * @param array $data Input data
     * @param array $rules Validation rules [field => rules]
     * @param array $messages Optional custom messages [field => [rule => message]]
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function quick($data, $rules, $messages = []) {
        $validator = new self();

        // Set rules
        foreach ($rules as $field => $fieldRules) {
            $validator->setRule($field, $fieldRules);
        }

        // Set custom messages
        foreach ($messages as $field => $fieldMessages) {
            foreach ($fieldMessages as $rule => $message) {
                $validator->setMessage($field, $rule, $message);
            }
        }

        // Validate
        $valid = $validator->validate($data);

        return [
            'valid' => $valid,
            'errors' => $validator->getErrors(),
            'data' => $validator->getValidated()
        ];
    }
}
?>
