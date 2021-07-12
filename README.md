# dto-attribute-codemod

This is a "worked for me" code mod to go from doctrine annotations to attributes for configuring the ORM. Probably not useful as is, but as an example for anyone else looking to make a similiar change.

## Usage:

`vendor/bin/codeshift --mod=mod.php --src=/SOMEWHERE/src/SOMETHING`

## before:

```php
<?php

declare(strict_types=1);

namespace App\Entity\DTO;

use App\Annotation as IS;

/**
 * @IS\DTO("courses")
 */
class CourseDTO
{
    /**
     * @IS\Id
     * @IS\Expose
     * @IS\Type("integer")
     */
    public int $id;

    /**
     * @IS\Expose
     * @IS\Type("string")
     */
    public ?string $title;

```

## after:

```php
<?php

declare(strict_types=1);

namespace App\Entity\DTO;

use App\Attribute as IA;
use DateTime;

#[IA\DTO('courses')]
class CourseDTO
{
    #[IA\Id]
    #[IA\Expose]
    #[IA\Type('integer')]
    public int $id;

    #[IA\Expose]
    #[IA\Type('string')]
    public ?string $title;
```


Thanks especailly to [PHP Codeshift](https://github.com/Atanamo/PHP-Codeshift) for making this easy to run and to [PHP-Parser](https://github.com/nikic/PHP-Parser).