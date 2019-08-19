<?php

namespace MLocati\C5SinceTagger\Extractor;

class Serializer
{
    /**
     * @var string
     */
    const OUTPUT_WEBROOT = '/var/www';

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $webroot;

    /**
     * @var array|null
     */
    private $facadeList;

    /**
     * @var array|null
     */
    private $classAliasList;

    /**
     * @var \MLocati\C5SinceTagger\Extractor\PhpDoc\Extractor
     */
    private $phpDocExtractor;

    /**
     * @var \MLocati\C5SinceTagger\Extractor\PhpDoc\TokenExtractor
     */
    private $phpDocTokenExtractor;

    /**
     * @param string $version
     * @param string $webroot
     */
    public function __construct($version, $webroot)
    {
        $this->version = $version;
        $this->webroot = $webroot;
        $this->phpDocExtractor = new PhpDoc\Extractor();
        $this->phpDocTokenExtractor = new PhpDoc\TokenExtractor($this->webroot, $this->phpDocExtractor);
    }

    /**
     * @return [int, int]
     */
    public function loadClassAliases()
    {
        $aliases = $this->getFacadeList() + $this->getClassAliasList();
        $count = 0;
        $total = \count($aliases);
        foreach (\array_keys($aliases) as $aliasName) {
            $skip = false;
            switch ($aliasName) {
                case 'PermissionCache':
                    if (\version_compare($this->version, '5.7.2') >= 0 && \version_compare($this->version, '5.7.3.1') <= 0) {
                        $skip = true;
                    }
                    break;
            }
            if ($skip === false && \class_exists($aliasName, true)) {
                $count++;
            }
        }

        return [$count, $total];
    }

    /**
     * @return \stdClass
     */
    public function serialize()
    {
        $result = new \stdClass();
        $result->version = $this->version;
        $result->parsedAt = \time();

        echo 'Serializing global constants... ';
        $result->globalConstants = $this->serializeGlobalConstants();
        echo \count($result->globalConstants), " global constants found.\n";

        echo 'Serializing global functions... ';
        $result->globalFunctions = $this->serializeGlobalFunctions();
        echo \count($result->globalFunctions), " global functions found.\n";

        echo 'Serializing interfaces... ';
        $result->interfaces = $this->serializeInterfaces();
        echo \count($result->interfaces), " interfaces found.\n";

        echo 'Serializing classes... ';
        $result->classes = $this->serializeClasses();
        echo \count($result->classes), " classes found.\n";

        echo 'Serializing class aliases... ';
        $result->classAliases = $this->serializeClassAliases();
        echo \count($result->classAliases), " class aliases found.\n";

        echo 'Serializing traits... ';
        $result->traits = $this->serializeTraits();
        echo \count($result->traits), " traits found.\n";

        $this->phpDocTokenExtractor->process();

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    private function serializeGlobalConstants()
    {
        $result = [];
        $allConstants = \get_defined_constants(true);
        if (!isset($allConstants['user'])) {
            return $result;
        }
        $constants = $allConstants['user'];
        if (\count($constants) === 0) {
            return $result;
        }
        \ksort($constants);
        foreach ($constants as $name => $value) {
            if (\stripos($name, __NAMESPACE__ . '\\') === 0) {
                continue;
            }

            if ($name === 'C5_EXECUTE') {
                $value = 'unique-hash-id';
            }
            if (\is_string($value)) {
                if (\stripos(PHP_OS, 'WIN') === 0 && \strpos($value, '\\') !== false) {
                    if (\strpos($name, 'DIR_') === 0) {
                        $value = \str_replace(\DIRECTORY_SEPARATOR, '/', $value);
                    }
                }
                $path = \str_replace(\DIRECTORY_SEPARATOR, '/', $value);
                if ($path === $this->webroot) {
                    $value = self::OUTPUT_WEBROOT;
                } elseif (\strpos($path, "{$this->webroot}/") === 0) {
                    $value = self::OUTPUT_WEBROOT . \substr($value, \strlen($this->webroot));
                }
            }
            $obj = new \stdClass();
            $obj->name = $name;
            $obj->value = $value;
            $obj->definedAt = '';
            $result[] = $obj;
        }
        $this->finalizeGlobalConstants($result);

        return $result;
    }

    /**
     * @param \stdClass[] $list
     */
    private function finalizeGlobalConstants(array $constants)
    {
        $lister = new Filesystem\FileLister($this->webroot, $this->version);
        $this->finalizeGlobalConstantsInDirectory($constants, $lister->listFilesIn(false, 'concrete/bootstrap'));
        foreach ($constants as $constant) {
            if ($constant->definedAt === '') {
                switch ($constant->name) {
                    case 'DIRECTORY_PERMISSIONS_MODE':
                    case 'FILE_PERMISSIONS_MODE':
                        $this->finalizeGlobalConstantsInFile($constants, 'concrete/src/Application/Application.php');
                        break;
                    case 'APP_CHARSET':
                    case 'APP_VERSION':
                    case 'DIR_REL':
                        $this->finalizeGlobalConstantsInFile($constants, 'concrete/src/Foundation/Runtime/Boot/DefaultBooter.php');
                        break;
                    case 'USE_MB_STRING':
                        $this->finalizeGlobalConstantsInFile($constants, 'concrete/vendor/indigophp/hash-compat/src/hash_equals.php');
                        break;
                    case 'RANDOM_COMPAT_READ_BUFFER':
                        $this->finalizeGlobalConstantsInFile($constants, 'concrete/vendor/paragonie/random_compat/lib/random.php');
                        break;
                    case 'GRAPHEME_CLUSTER_RX':
                        if (\is_file("{$this->webroot}/concrete/vendor/patchwork/utf8/class/Patchwork/Utf8/Bootup.php")) {
                            $this->finalizeGlobalConstantsInFile($constants, 'concrete/vendor/patchwork/utf8/class/Patchwork/Utf8/Bootup.php');
                        } else {
                            $this->finalizeGlobalConstantsInFile($constants, 'concrete/vendor/patchwork/utf8/src/Patchwork/Utf8/Bootup.php');
                        }
                        break;
                    case 'PHP_INT_MIN':
                        $this->finalizeGlobalConstantsInFile($constants, 'concrete/vendor/symfony/polyfill-php70/bootstrap.php');
                        break;
                }
                if ($constant->definedAt === '') {
                    throw new \Exception("Unable to find where the global constant {$constant->name} is defined");
                }
            }
            $this->phpDocTokenExtractor->queue($constant);
        }
    }

    private function finalizeGlobalConstantsInDirectory(array $constants, \Generator $files)
    {
        foreach ($files as $file) {
            $this->finalizeGlobalConstantsInFile($constants, $file);
        }
    }

    private function finalizeGlobalConstantsInFile(array $constants, $file)
    {
        $php = \file_get_contents("{$this->webroot}/{$file}");
        if ($php === false) {
            throw new \Exception("Failed to read {$file}");
        }
        $tokens = \token_get_all($php);
        foreach ($tokens as $index => $token) {
            if (!\is_array($token)) {
                continue;
            }
            if ($token[0] === T_CONST) {
                $this->finalizeGlobalConstantsInFileConst($constants, $file, $tokens, $index);
            } elseif ($token[0] === T_STRING && \strcasecmp(\trim($token[1]), 'define') === 0) {
                $this->finalizeGlobalConstantsInFileDefine($constants, $file, $tokens, $index);
            }
        }
    }

    private function finalizeGlobalConstantsInFileConst(array $constants, $file, array &$tokens, $index)
    {
        $line = $tokens[$index][2];
        $offset = $this->skipWhitespacesComments($tokens, $index + 1);
        if ($offset === null) {
            return;
        }
        if (!\is_array($tokens[$offset]) || $tokens[$offset][0] != T_STRING) {
            return;
        }
        $name = $tokens[$offset][1];
        $offset = $this->skipWhitespacesComments($tokens, $offset + 1);
        if ($offset === null || $tokens[$offset] !== '=') {
            return;
        }
        foreach ($constants as $constant) {
            if ($constant->name === $name && $constant->definedAt === '') {
                $constant->definedAt = "${file}:${line}";
                break;
            }
        }
    }

    private function finalizeGlobalConstantsInFileDefine(array $constants, $file, array &$tokens, $index)
    {
        $line = $tokens[$index][2];
        $offset = $this->skipWhitespacesComments($tokens, $index + 1);
        if ($offset === null) {
            return;
        }
        if ($tokens[$offset] !== '(') {
            return;
        }
        $offset = $this->skipWhitespacesComments($tokens, $offset + 1);
        if ($offset === null) {
            return;
        }
        if (!\is_array($tokens[$offset]) || $tokens[$offset][0] != T_CONSTANT_ENCAPSED_STRING) {
            return;
        }
        $name = $tokens[$offset][1];
        if (!\preg_match('/^["\'](\w+)["\']$/', $name)) {
            return;
        }
        $name = \substr($name, 1, -1);
        $offset = $this->skipWhitespacesComments($tokens, $offset + 1);
        if ($offset === null) {
            return;
        }
        if ($tokens[$offset] !== ',') {
            return;
        }
        foreach ($constants as $constant) {
            if ($constant->name === $name && $constant->definedAt === '') {
                $constant->definedAt = "${file}:${line}";
                break;
            }
        }
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int|null
     */
    private function skipWhitespacesComments(array &$tokens, $index)
    {
        for (;;) {
            if (!isset($tokens[$index])) {
                return null;
            }
            if (!\is_array($tokens[$index]) || !\in_array($tokens[$index][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
            $index++;
        }
    }

    /**
     * @return \stdClass[]
     */
    private function serializeGlobalFunctions()
    {
        $result = [];
        $allFunctions = \get_defined_functions();
        if (!isset($allFunctions['user'])) {
            return 0;
        }
        $functions = \array_filter(
            $allFunctions['user'],
            function ($name) {
                if (\stripos($name, __NAMESPACE__ . '\\') === 0) {
                    return false;
                }
                if (\strcasecmp($name, 'composer\autoload\includefile') === 0) {
                    return false;
                }
                if (\preg_match('/^composerrequire\w{32}$/i', $name)) {
                    return false;
                }

                return true;
            }
            );
        if (\count($functions) === 0) {
            return 0;
        }
        \natcasesort($functions);
        foreach ($functions as $name) {
            $result[] = $this->serializeGlobalFunction(new \ReflectionFunction($name));
        }

        return $result;
    }

    /**
     * @param \ReflectionFunction $function
     *
     * @return \stdClass
     */
    private function serializeGlobalFunction(\ReflectionFunction $function)
    {
        $result = $this->serializeFunction($function);

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    private function serializeInterfaces()
    {
        $result = [];
        $interfaceNames = \get_declared_interfaces();
        \natcasesort($interfaceNames);
        foreach ($interfaceNames as $interfaceName) {
            $interface = new \ReflectionClass($interfaceName);
            if ($interface->isUserDefined()) {
                $result[] = $this->serializeInterface($interface);
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $interface
     *
     * @return \stdClass
     */
    private function serializeInterface(\ReflectionClass $interface)
    {
        $result = $this->serializeICT($interface);
        $result->parentInterfaces = $this->getActualInterfaces($interface);
        $result->constants = $this->serializeICConstants($interface);

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    private function serializeClasses()
    {
        $result = [];
        $classNames = \get_declared_classes();
        \natcasesort($classNames);
        foreach ($classNames as $className) {
            if (\stripos($className, __NAMESPACE__ . '\\') === 0 || \preg_match('/^ComposerAutoloaderInit\w{32}$/i', $className)) {
                continue;
            }
            $class = new \ReflectionClass($className);
            if ($class->isUserDefined() && \strcasecmp($class->getName(), $className) === 0) {
                $result[] = $this->serializeClass($class);
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return \stdClass
     */
    private function serializeClass(\ReflectionClass $class)
    {
        $result = $this->serializeICT($class);
        $result->final = $class->isFinal();
        if ($result->name === 'Concrete\Core\Attribute\ExpressSetManager' && $this->version === '8.0.1') {
            $result->abstract = false;
        } else {
            $result->abstract = $class->isAbstract();
        }
        $result->traits = [];
        $traitAliases = $class->getTraitAliases();
        foreach ($class->getTraitNames() as $traitName) {
            $traitData = new \stdClass();
            $traitData->name = $traitName;
            $traitData->aliases = [];
            $prefix = $traitName . '::';
            foreach (\array_keys($traitAliases) as $alias) {
                $name = $traitAliases[$alias];
                if (\strpos($name, $prefix) === 0) {
                    $aliasData = new \stdClass();
                    $aliasData->originalName = \substr($name, \strlen($prefix));
                    $aliasData->alias = $alias;
                    $traitData->aliases[] = $aliasData;
                    unset($traitAliases[$alias]);
                }
            }
            $result->traits[] = $traitData;
        }
        if ($traitAliases !== []) {
            throw new \Exception('Unrecognized trait aliases: ' . \print_r($traitAliases, true));
        }
        $parentClass = $class->getParentClass();
        $interfaces = $this->getActualInterfaces($class);
        if ($parentClass) {
            $parentClassName = $parentClass->getName();
            $parentInterfaces = $parentClass->getInterfaceNames();
            $interfaces = \array_filter($interfaces, function ($interface) use ($parentInterfaces) {
                return \in_array($interface, $parentInterfaces) === false;
            });
        } else {
            $parentClassName = '';
        }
        $result->parentClassName = $parentClassName;
        \usort($interfaces, 'strnatcasecmp');
        $result->implements = $interfaces;
        $result->constants = $this->serializeICConstants($class);
        $result->properties = $this->serializeCTProperties($class);

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    private function serializeClassAliases()
    {
        $result = [];
        $classNames = \get_declared_classes();
        \natcasesort($classNames);
        foreach ($classNames as $className) {
            if (\stripos($className, __NAMESPACE__ . '\\') === 0) {
                continue;
            }
            $class = new \ReflectionClass($className);
            $actualClassName = $class->getName();
            if (\strcasecmp($actualClassName, $className) !== 0) {
                switch (\strtolower($className)) {
                    case 'stackable':
                        break;
                    default:
                        $result[] = $this->serializeClassAlias($class, $className);
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $actualClass
     * @param string $aliasName
     *
     * @return \stdClass
     */
    private function serializeClassAlias(\ReflectionClass $actualClass, $aliasName)
    {
        $result = new \stdClass();
        $result->actualClass = $actualClass->getName();
        $result->alias = $this->getClassAliasDisplayName($aliasName);

        return $result;
    }

    /**
     * @param string $runtimeName
     *
     * @return string
     */
    private function getClassAliasDisplayName($runtimeName)
    {
        $runtimeNameLC = \strtolower($runtimeName);
        $keys = \array_keys($this->getFacadeList());
        $map = \array_change_key_case(\array_combine($keys, $keys), \CASE_LOWER);
        if (isset($map[$runtimeNameLC])) {
            return $map[$runtimeNameLC];
        }
        $keys = \array_keys($this->getClassAliasList());
        $map = \array_change_key_case(\array_combine($keys, $keys), \CASE_LOWER);
        if (isset($map[$runtimeNameLC])) {
            return $map[$runtimeNameLC];
        }
        switch ($runtimeNameLC) {
            case 'concrete\core\tools\console\doctrine\consolerunner':
                return 'Concrete\Core\Tools\Console\Doctrine\ConsoleRunner';
            default:
                throw new \Exception("Unrecognized class alias: {$runtimeName}");
        }
    }

    /**
     * @return \stdClass[]
     */
    private function serializeTraits()
    {
        $result = [];
        if (!\method_exists('ReflectionClass', 'isTrait')) {
            return $result;
        }
        $traitNames = \get_declared_traits();
        \natcasesort($traitNames);
        foreach ($traitNames as $traitName) {
            $trait = new \ReflectionClass($traitName);
            if ($trait->isUserDefined()) {
                $result[] = $this->serializeTrait($trait);
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $trait
     *
     * @return \stdClass
     */
    private function serializeTrait(\ReflectionClass $trait)
    {
        $result = $this->serializeICT($trait);
        $result->properties = $this->serializeCTProperties($trait);

        return $result;
    }

    /**
     * @param \ReflectionFunctionAbstract $function
     *
     * @return \stdClass
     */
    private function serializeFunction(\ReflectionFunctionAbstract $function)
    {
        $result = new \stdClass();
        $result->name = $function->getName();
        $result->returnsReference = $function->returnsReference();
        $returnType = \method_exists($function, 'hasReturnType') && $function->hasReturnType() ? $function->getReturnType() : null;
        if ($returnType === null) {
            $result->returnType = '';
            $result->returnTypeAllowsNull = null;
        } else {
            $result->returnType = $returnType->getName();
            $result->returnTypeAllowsNull = (bool) $returnType->allowsNull();
        }
        $result->parameters = [];
        $usedNames = [];
        foreach ($function->getParameters() as $parameter) {
            $result->parameters[] = $this->serializeFunctionParameter($parameter, $usedNames);
        }
        $result->generator = \method_exists($function, 'isGenerator') && $function->isGenerator();
        $result->definedAt = $this->normalizeDefinedAt($function->getFileName(), $function->getStartLine());
        $result->since = $this->phpDocExtractor->extractSince($function->getDocComment() ?: '');

        return $result;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param string[] $usedNames
     *
     * @return \stdClass
     */
    private function serializeFunctionParameter(\ReflectionParameter $parameter, array &$usedNames)
    {
        $result = new \stdClass();
        $name = (string) $parameter->getName();
        if ($name === '') {
            for ($i = 1;; ++$i) {
                $name = 'foo' . $i;
                if (!\in_array($name, $usedNames, true)) {
                    break;
                }
            }
        }
        $usedNames[] = $name;
        $result->name = $name;
        $type = '';
        $allowsNull = $parameter->allowsNull();
        $parameterType = \method_exists($parameter, 'hasType') && $parameter->hasType() ? $parameter->getType() : null;
        if ($parameterType !== null) {
            $type = $parameterType->getName();
            $allowsNull = $allowsNull || $parameterType->allowsNull();
        } else {
            try {
                $type = $parameter->getClass() ? $parameter->getClass()->getName() : '';
            } catch (\Exception $exception) {
                $m = null;
                if (!\preg_match('/Class ([\w\\\\]+) does not exist/', $exception->getMessage(), $m)) {
                    throw $exception;
                }
                $type = $m[1];
            }
        }
        if ($type === '' && \method_exists($parameter, 'isCallable') && $parameter->isCallable()) {
            $type = 'callable';
        }
        if ($parameter->isArray()) {
            if ($type === '') {
                $type = 'array';
            } else {
                $type .= '[]';
            }
        }
        $result->type = $type;
        $result->allowsNull = $allowsNull;
        $result->byReference = $parameter->isPassedByReference();
        $result->variadic = \method_exists($parameter, 'isVariadic') && $parameter->isVariadic();
        $result->optional = $parameter->isOptional();
        if ($parameter->isOptional()) {
            $d = $parameter->getDefaultValueConstantName();
            if ($d !== null) {
                $result->defaultValueConstantName = $d;
            } else {
                $result->defaultValue = $parameter->getDefaultValue();
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $ict
     *
     * @return \stdClass
     */
    private function serializeICT(\ReflectionClass $ict)
    {
        $result = new \stdClass();
        $result->name = $ict->getName();
        $result->methods = $this->serializeICTMethods($ict);
        $result->definedAt = $this->normalizeDefinedAt($ict->getFileName(), $ict->getStartLine());
        $result->since = $this->phpDocExtractor->extractSince($ict->getDocComment() ?: '');

        return $result;
    }

    /**
     * @param \ReflectionClass $ic
     *
     * @return \stdClass[]
     */
    private function serializeICConstants(\ReflectionClass $ic)
    {
        $result = [];
        foreach ($this->getActualICTConstants($ic) as $constantName => $constantValue) {
            $result[] = $this->serializeICConstant($ic, $constantName, $constantValue);
        }
        if ($result !== []) {
            $this->finalizeICConstants($ic, $result);
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $ic
     * @param string $constantName
     * @param mixed $constantValue
     *
     * @return \stdClass[]
     */
    private function serializeICConstant(\ReflectionClass $ic, $constantName, $constantValue)
    {
        $result = new \stdClass();
        $result->name = $constantName;
        $result->value = $constantValue;
        $result->definedAt = '';
        if (!$ic->isInterface()) {
            $refl = \method_exists($ic, 'getReflectionConstant') ? $ic->getReflectionConstant($constantName) : null;
            if ($refl !== null && $refl->isPrivate()) {
                $result->visibility = 'private';
            } elseif ($refl !== null && $refl->isProtected()) {
                $result->visibility = 'protected';
            } else {
                $result->visibility = 'public';
            }
        }

        return $result;
    }

    private function finalizeICConstants(\ReflectionClass $ic, array $constants)
    {
        $tokens = $this->getRootICTUsefulTokens($ic);
        $normalizedFilename = \str_replace(\DIRECTORY_SEPARATOR, '/', \substr($ic->getFileName(), \strlen($this->webroot) + 1));
        foreach ($tokens as $index => $token) {
            if (\is_array($token) && $token[0] === T_CONST && isset($tokens[$index + 2]) && $tokens[$index + 2] === '=' && \is_array($tokens[$index + 1]) && $tokens[$index + 1][0] === T_STRING) {
                $constantName = $tokens[$index + 1][1];

                if (isset($tokens[$index - 1]) && \is_array($tokens[$index - 1]) && \in_array($tokens[$index - 1][2], [T_PRIVATE, T_PROTECTED, T_PUBLIC], true)) {
                    $line = $tokens[$index - 1][2];
                } else {
                    $line = $token[2];
                }
                foreach ($constants as $constant) {
                    if ($constant->name === $constantName) {
                        if ($constant->definedAt === '') {
                            $constant->definedAt = $normalizedFilename . ':' . $line;
                            $this->phpDocTokenExtractor->queue($constant);
                        }
                        break;
                    }
                }
            }
        }
        foreach ($constants as $constant) {
            if ($constant->definedAt === '') {
                throw new \Exception("Unable to find where the class constant {$ic->name}::{$constant->name} is defined");
            }
        }
    }

    /**
     * @param \ReflectionClass $ct
     *
     * @return \stdClass[]
     */
    private function serializeCTProperties(\ReflectionClass $ct)
    {
        $result = [];
        $defaultProperties = $ct->getDefaultProperties();
        foreach ($this->getActualProperties($ct) as $property) {
            $result[] = $this->serializeCTProperty($property, isset($defaultProperties[$property->getName()]) ? $defaultProperties[$property->getName()] : null);
        }
        $this->finalizeCTProperties($ct, $result);

        return $result;
    }

    /**
     * @param \ReflectionProperty $property
     * @param mixed $defaultValue
     *
     * @return \stdClass[]
     */
    private function serializeCTProperty(\ReflectionProperty $property, $defaultValue)
    {
        $result = new \stdClass();
        $result->name = $property->getName();
        $result->static = $property->isStatic();
        $result->definedAt = '';
        if ($property->isPrivate()) {
            $result->visibility = 'private';
        } elseif ($property->isProtected()) {
            $result->visibility = 'protected';
        } else {
            $result->visibility = 'public';
        }
        if ($property->name === 'directory' && $property->class === 'Punic\Data') {
            $defaultValue = null;
        }
        $defaultValueConstantName = $this->findDefaultValueConstantName($defaultValue);
        if ($defaultValueConstantName !== null) {
            $result->defaultValueConstantName = $defaultValueConstantName;
        } else {
            $result->defaultValue = $defaultValue;
        }

        $propertyType = \method_exists($property, 'hasType') && $property->hasType() ? $property->getType() : null;
        $result->type = $propertyType === null ? '' : $propertyType->getName();
        $result->typeAllowsNull = $propertyType === null ? null : (bool) $propertyType->allowsNull();
        $result->since = $this->phpDocExtractor->extractSince($property->getDocComment() ?: '');

        return $result;
    }

    /**
     * @param \ReflectionClass $ct
     * @param \stdClass $properties
     */
    private function finalizeCTProperties(\ReflectionClass $ct, array $properties)
    {
        if (\count($properties) === 0) {
            return;
        }
        $tokens = $this->getRootICTUsefulTokens($ct);
        $normalizedFilename = \str_replace(\DIRECTORY_SEPARATOR, '/', \substr($ct->getFileName(), \strlen($this->webroot) + 1));
        $count = \count($tokens);
        $parenthesis = 0;
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if ($token === '(') {
                $parenthesis++;
                continue;
            }
            if ($token === ')') {
                $parenthesis--;
                continue;
            }
            if ($parenthesis !== 0) {
                continue;
            }
            if (!\is_array($token) || $token[0] !== T_VARIABLE) {
                continue;
            }
            if ($index >= 0 && \is_array($tokens[$index - 1]) && \in_array($tokens[$index - 1][0], [T_PRIVATE, T_PROTECTED, T_PUBLIC], true)) {
                $line = $tokens[$index - 1][2];
            } else {
                $line = $token[2];
            }
            $propertyName = \ltrim($token[1], '$');
            foreach ($properties as $property) {
                if ($property->name === $propertyName) {
                    $property->definedAt = $normalizedFilename . ':' . $line;
                    break;
                }
            }
        }
        foreach ($properties as $property) {
            if ($property->definedAt === '') {
                throw new \Exception("Unable to find where the property {$ct->name}::{$property->name} is defined");
            }
        }
    }

    /**
     * @param mixed $defaultValue
     *
     * @return string|null
     */
    private function findDefaultValueConstantName($defaultValue)
    {
        if (!\is_string($defaultValue)) {
            return null;
        }
        $path = \str_replace(\DIRECTORY_SEPARATOR, '/', $defaultValue);
        if (\stripos($path, $this->webroot) !== 0) {
            return null;
        }
        $compatibleConstantsFound = [];
        $allConstants = \get_defined_constants(true);
        if (isset($allConstants['user'])) {
            foreach ($allConstants['user'] as $name => $value) {
                if (\stripos($name, __NAMESPACE__ . '\\') !== 0) {
                    if ($defaultValue === $value) {
                        $compatibleConstantsFound[] = $name;
                    }
                }
            }
        }
        switch (\count($compatibleConstantsFound)) {
            case 0:
                throw new \Exception('No compatible global constants found');
            case 1:
                return $compatibleConstantsFound[0];
            default:
                throw new \Exception('Too many compatible global constants found');
        }
    }

    /**
     * @param \ReflectionClass $ict
     *
     * @return \stdClass[]
     */
    private function serializeICTMethods(\ReflectionClass $ict)
    {
        $result = [];
        foreach ($this->getActualMethods($ict) as $method) {
            $result[] = $this->serializeICTMethod($ict, $method);
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $ict
     * @param \ReflectionMethod $method
     *
     * @return \stdClass[]
     */
    private function serializeICTMethod(\ReflectionClass $ict, \ReflectionMethod $method)
    {
        $result = $this->serializeFunction($method);
        $result->static = $method->isStatic();
        if (!$ict->isInterface()) {
            $result->final = $method->isFinal();
            $result->abstract = $method->isAbstract();
            if ($method->isPrivate()) {
                $result->visibility = 'private';
            } elseif ($method->isProtected()) {
                $result->visibility = 'protected';
            } else {
                $result->visibility = 'public';
            }
        }

        return $result;
    }

    /**
     * @param string|null $filename
     * @param int|null $line
     *
     * @return string
     */
    private function normalizeDefinedAt($filename, $line)
    {
        $filename = \str_replace(\DIRECTORY_SEPARATOR, '/', (string) $filename);
        if (\strpos($filename, "{$this->webroot}/") !== 0) {
            return '';
        }
        $result = \substr($filename, \strlen($this->webroot) + 1);
        $line = (int) $line;
        if ($line > 0) {
            $result .= ':' . $line;
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return string[]
     */
    private function getActualInterfaces(\ReflectionClass $ic)
    {
        $result = [];
        $allInterfaces = $ic->getInterfaceNames();
        foreach ($allInterfaces as $interface) {
            $isInherited = false;
            foreach ($allInterfaces as $otherInterface) {
                if ($otherInterface !== $interface && \in_array($interface, \class_implements($otherInterface)) === true) {
                    $isInherited = true;
                    break;
                }
            }
            if ($isInherited === false) {
                $result[] = $interface;
            }
        }
        \usort($result, 'strnatcasecmp');

        return $result;
    }

    /**
     * Get the actual constants of an interface/class (that is, excluding the inherited ones).
     *
     * @param \ReflectionClass $ic
     *
     * @return array array keys are the constant names, array values are the constant values
     */
    private function getActualICTConstants(\ReflectionClass $ic)
    {
        $constants = $ic->getConstants();
        $parentClasses = $ic->getInterfaces();
        $parentClass = $ic->getParentClass();
        if ($parentClass) {
            $parentClasses[] = $parentClass;
        }
        foreach ($parentClasses as $parentClass) {
            $parentClassConstants = $parentClass->getConstants();
            $commonConstantNames = \array_intersect(\array_keys($constants), \array_keys($parentClassConstants));
            foreach ($commonConstantNames as $commonConstantName) {
                if ($parentClass->getConstant($commonConstantName) === $constants[$commonConstantName]) {
                    unset($constants[$commonConstantName]);
                }
            }
        }

        return $constants;
    }

    /**
     * Get the actual properties of a class/trait (that is, excluding the inherited ones).
     *
     * @param \ReflectionClass $ict
     *
     * @return \ReflectionProperty[]
     */
    private function getActualProperties(\ReflectionClass $ict)
    {
        $traitProperties = [];
        foreach ($ict->getTraits() as $trait) {
            $traitProperties = \array_merge($traitProperties, \array_keys($trait->getDefaultProperties()));
        }
        $defaultProperties = $ict->getDefaultProperties();

        return \array_filter($ict->getProperties(), function (\ReflectionProperty $property) use ($ict, $traitProperties, $defaultProperties) {
            $name = $property->getName();
            if (\in_array($name, $traitProperties, true)) {
                return false;
            }
            if (!\array_key_exists($name, $defaultProperties)) {
                return false;
            }

            return $property->getDeclaringClass()->getName() === $ict->getName();
        });
    }

    /**
     * Get the actual methods of an interface/class/trait (that is, excluding the ones inherited from traits, parent classes/interfaces).
     *
     * @param \ReflectionClass $ict
     *
     * @return \ReflectionMethod[]
     */
    private function getActualMethods(\ReflectionClass $ict)
    {
        $traitAliases = $ict->getTraitAliases();
        $traitMethods = \array_keys($traitAliases);
        foreach ($ict->getTraits() as $trait) {
            /* @var \ReflectionClass $trait */
            foreach ($trait->getMethods() as $traitMethod) {
                $traitMethodFullName = $trait->getName() . '::' . $traitMethod->getName();
                if (!\in_array($traitMethodFullName, $traitAliases, true)) {
                    $traitMethods[] = $traitMethod->getName();
                }
            }
        }

        return \array_filter($ict->getMethods(), function (\ReflectionMethod $method) use ($ict, $traitMethods) {
            if (\in_array($method->name, $traitMethods, true)) {
                return false;
            }

            return $method->getDeclaringClass()->getName() === $ict->getName();
        });
    }

    /**
     * @return array
     */
    private function getFacadeList()
    {
        if ($this->facadeList === null) {
            switch ($this->version) {
                default:
                    $data = require $this->webroot . '/concrete/config/app.php';
                    if (!\is_array($data) || !isset($data['facades']) || !\is_array($data['facades'])) {
                        throw new \Exception('Unable to load the facade list');
                    }
                    $this->facadeList = $data['facades'];
                    break;
            }
        }

        return $this->facadeList;
    }

    /**
     * @return array
     */
    private function getClassAliasList()
    {
        if ($this->classAliasList === null) {
            switch ($this->version) {
                default:
                    $data = require $this->webroot . '/concrete/config/app.php';
                    if (!\is_array($data) || !isset($data['aliases']) || !\is_array($data['aliases'])) {
                        throw new \Exception('Unable to load the facade list');
                    }
                    $this->classAliasList = $data['aliases'];
                    break;
            }
        }

        return $this->classAliasList;
    }

    /**
     * @param \ReflectionClass $ict
     *
     * @return array
     */
    private function getRootICTUsefulTokens(\ReflectionClass $ict)
    {
        $php = \file_get_contents($ict->getFileName());
        if ($php === false) {
            throw new \Exception("Failed to read {$ict->getFileName()}");
        }
        $startLine = $ict->getStartLine();
        $endLine = $ict->getEndLine();
        $inClass = false;
        $classBodyTokens = [];
        foreach (\token_get_all($php) as $token) {
            if (\is_array($token) && \in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ($inClass) {
                if (\is_array($token) && $token[2] > $endLine) {
                    break;
                }
                $classBodyTokens[] = $token;
            } elseif (\is_array($token) && $token[2] >= $startLine) {
                $inClass = true;
            }
        }
        $usefulTokens = [];
        $level = 0;
        foreach ($classBodyTokens as $token) {
            if ($token === '}') {
                $level--;
            } elseif ($token === '{' || (\is_array($token) && $token[0] === T_CURLY_OPEN)) {
                $level++;
            } elseif ($level === 1) {
                if (!\is_array($token) || !\in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $usefulTokens[] = $token;
                }
            }
        }

        return $usefulTokens;
    }
}
