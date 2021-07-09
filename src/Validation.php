<?php

namespace Mozafar\Validation;

class Validation
{
    /**
     * Data to validate
     */
    private array $data = [];

    /**
     * Key value pair of field name and rules array
     * 
     * 'filed1' => ['rule1', 'rule2', ...]
     */
    private array $rules = [];

    /**
     * Key value pair of validation errors for each field
     */
    private array $errors = [];

    /**
     * Throw exception or not
     */
    private bool $throw = false;

    private array $validated = [];

    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = empty($data) ? $_REQUEST : $data;
        $this->rules = $rules;
    }

    public static function make(array $data = [], array $rules = []): self
    {
        return new static($data, $rules);
    }

    /**
     * In case of any errors throws an exception
     */
    public function throws(): self
    {
        $this->throw = true;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    public function pass()
    {
        return !$this->hasError();
    }

    public function validate(): bool
    {
        $data = array_merge($this->getRequired(), $this->data);
        foreach ($data as $key => $value) {
            if (!isset($this->rules[$key])) {
                continue;
            }
            foreach ($this->rules[$key] as $rule) {
                try {
                    $method = $this->getRuleMethod($rule);
                    if (!$this->hasRule($rule)) {
                        throw new RuleException("Rule [{$method}] is not defined");
                    }
                    $args = $this->getRuleArgs($rule);
                    $this->validated[$key] = $this->{$method}($value, ...$args);
                } catch (RuleException $e) {
                    $this->errors[$key] = $e->getMessage();
                    break;
                }
            }
        }

        if ($this->throw && $this->hasError()) {
            throw new ValidationException($this->errors);
        }
        
        return $this->pass();
    }

    public function validated(): array
    {
        if (! $this->validate()) {
            throw new ValidationException($this->errors);
        }
        
        return $this->validated;
    }

    private function getRuleMethod($rule)
    {
        return explode(':', $rule, 2)[0];
    }

    private function getRuleArgs($rule)
    {
        $exploded = explode(':', $rule, 2);
        if (count($exploded) == 1) {
            return [];
        }

        return explode(',', $exploded[1]);
    }

    private function hasRule(string $rule)
    {
        $method = $this->getRuleMethod($rule);
        return method_exists($this, $method);
    }

    private function getRequired(): array
    {
        $fields = [];
        foreach ($this->rules as $name => $rules) {
            if (in_array('required', $rules)) {
                $fields[$name] = '';
            }
        }
        return $fields;
    }

    private function required($val)
    {
        if (!isset($val) || empty($val)) {
            $this->throwError('Data is required');
        }

        return $val;
    }

    private function integer($val): int
    {
        $val = filter_var($val, FILTER_VALIDATE_INT);
        if ($val === false) {
            $this->throwError('Invalid Integer');
        }
        return $val;
    }

    private function string($val): string
    {
        if (!is_string($val)) {
            $this->throwError('Invalid String');
        }
        $val = trim(htmlspecialchars($val));
        return $val;
    }

    private function bool($val): bool
    {
        $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
        return $val;
    }

    private function email($val): string
    {
        $val = filter_var($val, FILTER_VALIDATE_EMAIL);
        if ($val === false) {
            $this->throwError('Invalid Email');
        }
        return $val;
    }

    private function recaptcha($val, $url = null, $secret = null): string
    {
        if (empty($url)) {
            $this->throwError('Google API url required for recaptcha validation');
        }

        if (empty($secret)) {
            $this->throwError('Google API secret required for recaptcha validation');
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'secret' => $secret,
                'response' => $val
            ],
            CURLOPT_HTTPHEADER => [],
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            $this->throwError('recaptcha validation failed');
        }

        $response = json_decode($response, true);
        if (!isset($response['success']) || !$response['success']) {
            $this->throwError('Invalid recaptcha');
        }

        return $val;
    }

    private function throwError($error = 'Invalid value')
    {
        throw new RuleException($error);
    }
}
