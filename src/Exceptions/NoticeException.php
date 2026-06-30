<?php
/**
 * Base library exception.
 *
 * Thrown for programmer-error cases such as constructing a
 * {@see \DuckDev\Reviews\Support\KeyPrefixer} with an empty prefix or
 * building a {@see \DuckDev\Reviews\Support\Config} without the
 * required slug + name pair.
 *
 * Runtime conditions (notice not yet due, user dismissed it, current
 * screen disallowed, etc.) are signalled with boolean return values
 * rather than exceptions — those are expected outcomes, not errors.
 *
 * @link       https://duckdev.com/
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @author     Joel James <me@joelsays.com>
 * @since      2.0.0
 * @package    Reviews
 * @subpackage Exceptions
 */

namespace DuckDev\Reviews\Exceptions;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Exception;

/**
 * Class NoticeException.
 *
 * Marker subclass so consumers can catch library-specific failures
 * without swallowing unrelated exceptions.
 */
class NoticeException extends Exception {
}
