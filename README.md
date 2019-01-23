# serge

Swiftly Establish Rich GraphQL Endpoints

## Code generators

The following command line generates value objects for all descriptor
interfaces found in the specified PHP files and puts them inside
`src/Model/ValueObject` with the namespace
`\Kepawni\Serge\Model\ValueObject`.

Where the descriptor interfaces declare sub-namespaces these will be
reconstructed as subdirectories.

    php bin/codegen.php \
        --type ValueObject \
        --dir src/Model/ValueObject \
        --root-ns Kepawni.Serge.Model.ValueObject \
        src/Model/NamespaceDescriptor/ValueObject*.php

Namespaces can be specified using any punctuation character and one or
more such characters per component.

The following command line generates event classes for all descriptor
interfaces found in the specified PHP files and puts them inside
`src/Model/Event` with the namespace
`\Kepawni\Serge\Model\Event`. Where the descriptors declare unresolved
properties these are assumed to belong in the namespace
`\Kepawni\Serge\Model\ValueObject`

    php bin/codegen.php \
        --type EventPayload \
        --dir src/Model/Event \
        --root-ns Kepawni\\Serge:Model///Event \
        --param-ns Kepawni.Serge,Model@ValueObject \
        src/Model/NamespaceDescriptor/EventPayload*.php
