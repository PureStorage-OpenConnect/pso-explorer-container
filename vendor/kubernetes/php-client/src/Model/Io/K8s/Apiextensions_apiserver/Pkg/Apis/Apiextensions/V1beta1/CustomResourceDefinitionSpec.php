<?php

namespace Kubernetes\Model\Io\K8s\Apiextensions_apiserver\Pkg\Apis\Apiextensions\V1beta1;

/**
 * CustomResourceDefinitionSpec describes how a user wants their resource to appear
 */
class CustomResourceDefinitionSpec extends \KubernetesRuntime\AbstractModel
{

    /**
     * additionalPrinterColumns specifies additional columns returned in Table output.
     * See
     * https://kubernetes.io/docs/reference/using-api/api-concepts/#receiving-resources-as-tables
     * for details. If present, this field configures columns for all versions.
     * Top-level and per-version columns are mutually exclusive. If no top-level or
     * per-version columns are specified, a single column displaying the age of the
     * custom resource is used.
     *
     * @var CustomResourceColumnDefinition[]
     */
    public $additionalPrinterColumns = null;

    /**
     * conversion defines conversion settings for the CRD.
     *
     * @var CustomResourceConversion
     */
    public $conversion = null;

    /**
     * group is the API group of the defined custom resource. The custom resources are
     * served under `/apis/<group>/...`. Must match the name of the
     * CustomResourceDefinition (in the form `<names.plural>.<group>`).
     *
     * @var string
     */
    public $group = null;

    /**
     * names specify the resource and kind names for the custom resource.
     *
     * @var CustomResourceDefinitionNames
     */
    public $names = null;

    /**
     * preserveUnknownFields indicates that object fields which are not specified in
     * the OpenAPI schema should be preserved when persisting to storage. apiVersion,
     * kind, metadata and known fields inside metadata are always preserved. If false,
     * schemas must be defined for all versions. Defaults to true in v1beta for
     * backwards compatibility. Deprecated: will be required to be false in v1.
     * Preservation of unknown fields can be specified in the validation schema using
     * the `x-kubernetes-preserve-unknown-fields: true` extension. See
     * https://kubernetes.io/docs/tasks/access-kubernetes-api/custom-resources/custom-resource-definitions/#pruning-versus-preserving-unknown-fields
     * for details.
     *
     * @var boolean
     */
    public $preserveUnknownFields = null;

    /**
     * scope indicates whether the defined custom resource is cluster- or
     * namespace-scoped. Allowed values are `Cluster` and `Namespaced`. Default is
     * `Namespaced`.
     *
     * @var string
     */
    public $scope = null;

    /**
     * subresources specify what subresources the defined custom resource has. If
     * present, this field configures subresources for all versions. Top-level and
     * per-version subresources are mutually exclusive.
     *
     * @var CustomResourceSubresources
     */
    public $subresources = null;

    /**
     * validation describes the schema used for validation and pruning of the custom
     * resource. If present, this validation schema is used to validate all versions.
     * Top-level and per-version schemas are mutually exclusive.
     *
     * @var CustomResourceValidation
     */
    public $validation = null;

    /**
     * version is the API version of the defined custom resource. The custom resources
     * are served under `/apis/<group>/<version>/...`. Must match the name of the first
     * item in the `versions` list if `version` and `versions` are both specified.
     * Optional if `versions` is specified. Deprecated: use `versions` instead.
     *
     * @var string
     */
    public $version = null;

    /**
     * versions is the list of all API versions of the defined custom resource.
     * Optional if `version` is specified. The name of the first item in the `versions`
     * list must match the `version` field if `version` and `versions` are both
     * specified. Version names are used to compute the order in which served versions
     * are listed in API discovery. If the version string is "kube-like", it will sort
     * above non "kube-like" version strings, which are ordered lexicographically.
     * "Kube-like" versions start with a "v", then are followed by a number (the major
     * version), then optionally the string "alpha" or "beta" and another number (the
     * minor version). These are sorted first by GA > beta > alpha (where GA is a
     * version with no suffix such as beta or alpha), and then by comparing major
     * version, then minor version. An example sorted list of versions: v10, v2, v1,
     * v11beta2, v10beta3, v3beta1, v12alpha1, v11alpha2, foo1, foo10.
     *
     * @var CustomResourceDefinitionVersion[]
     */
    public $versions = null;


}
