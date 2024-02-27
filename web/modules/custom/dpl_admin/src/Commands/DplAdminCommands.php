<?php

namespace Drupal\dpl_admin\Commands;

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
class DplAdminCommands extends DrushCommands {
  use StringTranslationTrait;

  const TRANSLATIONS_DIR = 'profiles/dpl_cms/translations';

  /**
   *
   */
  public function __construct(protected CtpConfigManager $ctpConfigManager, protected FileSystemInterface $fileSystem) {}

  /**
   * Create a .po file with only the configuration strings.
   *
   * @command dpl_admin:extract-config
   * @usage drush dpl_admin:extract-config da da.po
   *   Extracts strings with config context and writes a fie with it.
   */
  public function createPoFileConfigOnly($langcode, $source) {
    $file = $this->extractTranslationsIntoFile($langcode, $source, '/^([a-z]+\.)+/');
    if (!$destination = $this->moveFile($file, $source, 'config')) {
      $this->io()->error($this->t('Could not create PO file.'));
      return;
    }

    $this->io()->success($this->t('File created on: @destination', ['@destination' => $destination]));
  }

  /**
   * Create a .po file with only the user interface strings.
   *
   * @command dpl_admin:extract-ui
   * @usage drush dpl_admin:extract-ui da da.po
   *   Extracts strings with config context and writes a fie with it.
   */
  public function createPoFileUiOnly($langcode, $source) {
    $file = $this->extractTranslationsIntoFile($langcode, $source, '/^([a-z]+\.)+/', 'exclude');
    if (!$destination = $this->moveFile($file, $source)) {
      $this->io()->error($this->t('Could not create PO file.'));
      return;
    }

    $this->io()->success($this->t('File created on: @destination', ['@destination' => $destination]));
  }

  /**
   *
   */
  protected function getDestination($source, $prefix) {
    if (!is_file($source)) {
      throw new \Exception('Invalid source file: ' . $source);
    }

    if (!is_readable($source)) {
      throw new \Exception('Unreadable source file: ' . $source);
    }

    $destination_dir = $this->fileSystem->realpath(self::TRANSLATIONS_DIR);
    // Check for writable destination.
    if (!is_writable($destination_dir)) {
      throw new \Exception('Destination dir is not writable: ' . $destination_dir);
    }

    if (!$prefix) {
      return sprintf('%s/%s', $destination_dir, $this->fileSystem->basename($source));
    }

    return sprintf('%s/%s.%s', $destination_dir, $prefix, $this->fileSystem->basename($source));
  }

  /**
   *
   */
  protected function moveFile($file, $source, $prefix = NULL) {
    if (!$destination = $this->getDestination($source, $prefix)) {
      return FALSE;
    }

    rename($file->getRealPath(), $destination);

    return $destination;
  }

  /**
   *
   */
  protected function extractTranslationsIntoFile($langcode, $source, $pattern, $mode = 'include') {
    $file            = new \stdClass();
    $file->filename  = $this->fileSystem->basename($source);
    $file->uri       = $source;
    $file->langcode  = $langcode;
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
