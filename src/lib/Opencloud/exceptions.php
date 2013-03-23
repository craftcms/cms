<?php
/**
 * The exceptions used by php-opencloud
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

/**
 * Top-level exceptions used by shared code
 */
namespace OpenCloud {

	/**
	 * General Exceptions
	 */
	class AttributeError extends \Exception {}
	class AuthenticationError extends \Exception {}
	class CollectionError extends \Exception {}
	class CredentialError extends \Exception {}
	class DocumentError extends \Exception {}
	class DomainError extends \Exception {}
	class EmptyResponseError extends \Exception {}
	class EndpointError extends \Exception {}
	class HttpError extends \Exception {}
	class HttpUnauthorizedError extends \Exception {}
	class HttpForbiddenError extends \Exception {}
	class HttpRetryError extends \Exception {}
	class HttpTimeoutError extends \Exception {}
	class HttpUrlError extends \Exception {}
	class HttpOverLimitError extends \Exception {}
	class IdRequiredError extends \Exception {}
	class InstanceNotFound extends \Exception {}
	class InvalidArgumentError extends \Exception {}
	class JsonError extends \Exception {}
	class ServerActionError extends \Exception {}
	class ServiceValueError extends \Exception {}
	class UnrecognizedServiceError extends \Exception {}
	class UrlError extends \Exception {}
	// used by PersistentObject
	class CreateError extends \Exception {}
	class DeleteError extends \Exception {}
	class UpdateError extends \Exception {}
	class NameError extends \Exception {}
	class UnsupportedExtensionError extends \Exception {}

	/**
	 * catch-all
	 */
	class UnknownError extends \Exception {}

}

/**
 * Exceptions used by the Compute service
 */
namespace OpenCloud\Compute {

	class ImageError extends \Exception {}
	class InvalidArgumentError extends \Exception {}
	class FlavorError extends \Exception {}
	class InvalidRequestError extends \Exception {}
	class InvalidParameterError extends \Exception {}
	class InvalidIpTypeError extends \Exception {}
	class MetadataError extends \Exception {}
	class MetadataKeyError extends \Exception {}
	class MetadataJsonError extends \Exception {}
	class MetadataCreateError extends \Exception {}
	class MetadataUpdateError extends \Exception {}
	class MetadataDeleteError extends \Exception {}
	class NetworkError extends \Exception {}
	class NetworkUrlError extends \Exception {}
	class NetworkCreateError extends \Exception {}
	class NetworkUpdateError extends \Exception {}
	class NetworkDeleteError extends \Exception {}
	class ServerCreateError extends \Exception {}
	class ServerUpdateError extends \Exception {}
	class ServerDeleteError extends \Exception {}
	class ServerIpsError extends \Exception {}
	class ServerUrlError extends \Exception {}
	class ServerJsonError extends \Exception {}
	class UnsupportedFeatureExtension extends \Exception {}
	class VolumeError extends \Exception {}

}

/**
 * Exceptions used by the Object Storage service
 */
namespace OpenCloud\ObjectStore {
    class CdnError extends \Exception {}
    class CdnNotAvailableError extends \Exception {}
    class CdnHttpError extends \Exception {}
    class CdnTtlError extends \Exception {}
    class CreateUpdateError extends \Exception {}
    class ContainerCreateError extends \Exception {}
    class ContainerDeleteError extends \Exception {}
    class ContainerError extends \Exception {}
    class ContainerNameError extends \Exception {}
    class ContainerNotFoundError extends \Exception {}
    class ContainerNotEmptyError extends \Exception {}
    class DeleteError extends \Exception {}
    class IOError extends \Exception {}
    class MetadataPrefixError extends \Exception {}
    class MisMatchedChecksumError extends \Exception {}
    class NoNameError extends \Exception {}
    class NoContentTypeError extends \Exception {}
    class ObjectError extends \Exception {}     // SyntaxException
    class ObjectCopyError extends \Exception {}
    class ObjFetchError extends \Exception {}
    class UnknownParameterError extends \Exception {}
}

/**
 * Exceptions used by the Cloud Databases service
 */
namespace OpenCloud\DbService {
	class DatabaseCreateError extends \Exception {}
	class DatabaseDeleteError extends \Exception {}
	class DatabaseListError extends \Exception {}
	class DatabaseUpdateError extends \Exception {}
    class DatabaseNameError extends \Exception {}
	class InstanceCreateError extends \Exception {}
	class InstanceDeleteError extends \Exception {}
	class InstanceUpdateError extends \Exception {}
    class InstanceError extends \Exception {}
    class InstanceFlavorError extends \Exception {}
	class InvalidIdTypeError extends \Exception {}
	class UserCreateError extends \Exception {}
	class UserDeleteError extends \Exception {}
	class UserListError extends \Exception {}
	class UserUpdateError extends \Exception {}
	class UserNameError extends \Exception {}
}

/**
 * Exceptions used by the Cloud Block Storage (CBS) service
 */
namespace OpenCloud\VolumeService {
	class SnapshotError extends \Exception {}
	class VolumeError extends \Exception {}
	class VolumeTypeError extends \Exception {}
}

/**
 * Exceptions used by the LoadBalancerService
 */
namespace OpenCloud\LoadBalancerService {
	class MissingValueError extends \Exception {}
}

/**
 * Exceptions used by the DNS service
 */
namespace OpenCloud\DNS {
	class AsyncHttpError extends \Exception {}
	class AsyncError extends \Exception {}
	class AsyncTimeoutError extends \Exception {}
	class RecordTypeError extends \Exception {}
}
