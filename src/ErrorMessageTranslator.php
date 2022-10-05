<?php

namespace Lnorby\MediaBundle;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ErrorMessageTranslator
{
//    /**
//     * @var TranslatorInterface
//     */
//    private $translatorCache = null;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translate(string $message, array $params, string $locale): string
    {
        return $this->translator->trans($message, $params, null, $locale);
    }

//    public function translator(): TranslatorInterface
//    {
//        if (null === $this->translatorCache) {
//            $this->translatorCache = new Translator('hu');
//            $this->translatorCache->addLoader('array', new ArrayLoader());
//            $this->translatorCache->addResource(
//                'array',
//                [
//                    'The file could not be uploaded.' => 'Hiba történt a fájl feltöltése közben.',
//                    'The file could not be found.' => 'A fájl nem található.',
//                    'The file is not readable.' => 'A fájl nem olvasható.',
//                    'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.' => 'A fájl túl nagy ({{ size }} {{ suffix }}). A maximálisan feltölthető méret {{ limit }} {{ suffix }}.',
//                    'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.' => 'A fájl mime típusa érvénytelen ({{ type }}). Engedélyezett mime típusok: {{ types }}.',
//                    'An empty file is not allowed.' => 'Üres fájl feltöltése nem engedélyezett.',
//                    'The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.' => 'A fájl túl nagy. A megengedett legnagyobb méret {{ limit }} {{ suffix }}.',
//                    'The file is too large.' => 'A fájl túl nagy.',
//                    'The file was only partially uploaded.' => 'A fájlt csak részben sikerült feltölteni.',
//                    'No file was uploaded.' => 'Nem lett kiválasztva fájl.',
//                    'No temporary folder was configured in php.ini.' => 'Nem lett ideiglenes mappa beállítva a php.ini-ben.',
//                    'Cannot write temporary file to disk.' => 'Nem lehet létrehozni az ideiglenes fájlt.',
//                    'A PHP extension caused the upload to fail.' => 'Egy PHP bővítmény megakadályozta a fájl feltöltését.',
//                    'This file is not a valid image.' => 'A megadott fájl nem kép.',
//                    'The size of the image could not be detected.' => 'Nem sikerült meghatározni a kép méretét.',
//                    'The image width is too big ({{ width }}px). Allowed maximum width is {{ max_width }}px.' => 'A kép szélessége túl nagy ({{ width }} pixel). A legnagyobb megengedett szélesség {{ max_width }} pixel.',
//                    'The image width is too small ({{ width }}px). Minimum width expected is {{ min_width }}px.' => 'A kép szélessége túl kicsi ({{ width }} pixel). A legkisebb megengedett szélesség {{ min_width }} pixel.',
//                    'The image height is too big ({{ height }}px). Allowed maximum height is {{ max_height }}px.' => 'A kép magassága túl nagy ({{ width }} pixel). A legnagyobb megengedett magasság {{ max_width }} pixel.',
//                    'The image height is too small ({{ height }}px). Minimum height expected is {{ min_height }}px.' => 'A kép magassága túl kicsi ({{ width }} pixel). A legkisebb megengedett magasság {{ max_width }} pixel.',
//                    'The image file is corrupted.' => 'A képfájl sérült.',
//                ],
//                'hu'
//            );
//        }
//
//        return $this->translatorCache;
//    }
}
