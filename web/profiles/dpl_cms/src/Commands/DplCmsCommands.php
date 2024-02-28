<?php

namespace Drupal\dpl_cms\Commands;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\config_translation_po\Services\CtpConfigManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

use function Safe\preg_match;

/**
 * A Drush commandfile.
 */
class DplCmsCommands extends DrushCommands {
  use StringTranslationTrait;

  const TRANSLATIONS_DIR = 'profiles/dpl_cms/translations';

  /**
   *
   */
  public function __construct(protected CtpConfigManager $ctpConfigManager, protected FileSystemInterface $fileSystem) {}

  /**
   * The source.
   */
  protected string $source;
  /**
   * The destination.
   */
  protected string $destination;
  /**
   * The language code of the proccessed po file.
   */
  protected string $languageCode;

  /**
   *
   */
  protected function setDestination(string $path) {
    $this->destination = $path;
  }

  /**
   *
   */
  protected function getDestination(): ?string {
    return $this->destination;
  }

  /**
   *
   */
  protected function setSource(string $path) {
    $this->source = $path;
  }

  /**
   *
   */
  protected function getSource(): ?string {
    return $this->source;
  }

  /**
   *
   */
  protected function setLanguageCode(string $langcode) {
    $this->languageCode = $langcode;
  }

  /**
   *
   */
  protected function getLanguageCode(): ?string {
    return $this->languageCode;
  }

  /**
   * Create a .po file with only the configuration strings.
   *
   * @param string $langcode
   *   The langcode to import. Eg. 'en' or 'fr'.
   * @param string $source
   *   The path to the source .po file.
   * @param string $destination
   *   The path to the destination .po file.
   *
   * @command dpl_cms:extract-config
   * @usage drush dpl_cms:extract-config da da.po
   *   Extracts strings with config context and writes a fie with it.
   */
  public function createPoFileConfigOnly($langcode, $source, $destination) {
    $this->setLanguageCode($langcode);
    $this->setSource($source);
    $this->setDestination($destination);

    $file = $this->extractTranslationsIntoFile('/^([a-z]+\.)+/');
    if (!$destination = $this->moveFile($file)) {
      $this->io()->error($this->t('Could not create PO file.'));
      return;
    }

    $this->io()->success($this->t('File created on: @destination', ['@destination' => $destination]));
  }

  /**
   * Create a .po file with only the user interface strings.
   *
   * @param string $langcode
   *   The langcode to import. Eg. 'en' or 'fr'.
   * @param string $source
   *   The path to the source .po file.
   * @param string $destination
   *   The path to the destination .po file.
   *
   * @command dpl_cms:extract-ui.
   * @usage drush dpl_cms:extract-ui da da.po
   *   Extracts strings with config context and writes a fie with it.
   */
  public function createPoFileUiOnly($langcode, $source, $destination) {
    $this->setLanguageCode($langcode);
    $this->setSource($source);
    $this->setDestination($destination);

    $file = $this->extractTranslationsIntoFile('/^([a-z]+\.)+/', 'exclude');
    if (!$destination = $this->moveFile($file)) {
      $this->io()->error($this->t('Could not create PO file.'));
      return;
    }

    $this->io()->success($this->t('File created on: @destination', ['@destination' => $destination]));
  }

  /**
   *
   */
  protected function validatePaths() {
    $source = $this->getSource();
    $destination = $this->getDestination();

    if (!is_file($source)) {
      throw new \Exception('Invalid source file: ' . $source);
    }

    if (!is_readable($source)) {
      throw new \Exception('Unreadable source file: ' . $source);
    }

    // Check for writable destination.
    $destination_dir = $this->fileSystem->dirname($destination);
    if (!is_writable($destination_dir)) {
      throw new \Exception('Destination dir is not writable: ' . $destination_dir);
    }

  }

  /**
   *
   */
  protected function moveFile($file) {
    $this->validatePaths();
    $destination = $this->getDestination();

    rename($file->getRealPath(), $destination);

    return $destination;
  }

  /**
   *
   */
  protected function extractTranslationsIntoFile($pattern, $mode = 'include') {
    $source = $this->getSource();

    $file            = new \stdClass();
    $file->filename  = $this->fileSystem->basename($source);
    $file->uri       = $source;
    $file->langcode  = $this->getLanguageCode();
    $file->timestamp = filemtime($source);

    $reader = new PoStreamReader();
    $reader->setLangcode($file->langcode);
    $reader->setURI($file->uri);

    try {
      $reader->open();
    }
    catch (\Exception $exception) {
      throw $exception;
    }

    $header = $reader->getHeader();
    if (!$header) {
      throw new \Exception('Missing or malformed header.');
    }

    $uri = $this->fileSystem->tempnam('temporary://', 'po_');
    $writer = new PoStreamWriter();
    $writer->setURI($uri);
    $writer->setHeader($header);
    $writer->open();

    while ($item = $reader->readItem()) {
      if ($mode === 'include' && preg_match($pattern, $item->getContext())) {
        $writer->writeItem($item);
      }
      if ($mode === 'exclude' && !preg_match($pattern, $item->getContext())) {
        $writer->writeItem($item);
      }
    }

    $writer->close();

    return new \SplFileInfo($this->fileSystem->realpath($uri));
  }

}
