<?php namespace Ske\Env;

class Env implements \ArrayAccess, \IteratorAggregate, \Countable {
    public function __construct(array $vars = []) {
   		$this->setAll($vars);
    }

    protected array $vars;

	public function setAll(array $vars): self {
		$this->vars = [];
		return $this->setAny($vars);
	}

	public function setAny(array $vars): self {
		foreach ($vars as $key => $value) {
			$this->set($key, $value);
		}
		return $this;
	}

	public function getAll(): array {
		return $this->vars;
	}

	public function getAny(array $vars): array {
		$values = [];
		foreach ($vars as $key => $value) {
			if ($this->isset($key)) {
				$values[$key] = $this->get($key);
			}
		}
		return $values;
	}

	public static function cast(array $data): self {
		return new self($data);
	}

	public static function parse(mixed $value): mixed {
		return match (true) {
			in_array($value, ['null', 'Null', 'NULL', 'nil', 'Nil', 'NIL', 'none', 'NONE'], true) => null,
			in_array($value, ['true', 'True', 'TRUE', 'on', 'On', 'ON', 'yes', 'Yes', 'YES', 't', 'T', 'y', 'Y', '1'], true) => true,
			in_array($value, ['false', 'False', 'FALSE', 'off', 'Off', 'OFF', 'no', 'No', 'NO', 'f', 'F', 'n', 'N', '0'], true) => false,
			is_numeric($value) => is_int(strpos($value, '.')) ? (float) $value : (int) $value,
			default => $value,
		};
	}

    public function set(string $key, mixed $value): self {
        if (is_array($value)) {
			foreach ($value as $k => $v) {
				$this->set($key . '_' . $k, $v);
			}
		}
		else {
			$key = strtoupper($key);
			$value = self::parse($value);
			putenv("$key=$value");
			$this->vars[$key] = $_ENV[$key] = $_SERVER[$key] = $value;
		}
		return $this;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->vars[$key] ?? $default;
    }

    public function isset(string $key): bool {
        return isset($this->vars[$key]);
    }

    public function unset(string $key): void {
        unset($this->vars[$key]);
    }

    public function offsetExists(mixed $offset): bool {
        return $this->isset($offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, $value): void {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        $this->unset($offset);
    }

    public function getIterator(): \ArrayIterator {
        return new \ArrayIterator($this->vars);
    }

    public function count(): int {
        return count($this->vars);
    }

    public function __set(string $key, mixed $value): void {
        $this->set($key, $value);
    }

    public function __get(string $key): mixed {
        return $this->get($key);
    }

    public function __isset(string $key): bool {
        return $this->isset($key);
    }

    public function __unset(string $key): void {
        $this->unset($key);
    }

    public function __debugInfo(): array {
        return $this->vars;
    }
}
