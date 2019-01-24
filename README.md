# serge

Swiftly Establish Rich GraphQL Endpoints

## Code generators

The following command line generates value objects for all descriptor interfaces found in the specified PHP files and puts them inside `src/Model/ValueObject` with the namespace `\Your\Project\Model\ValueObject`.

Where the descriptor interfaces declare sub-namespaces these will be reconstructed as subdirectories.

    php vendor/bin/serge-codegen.php \
        --type ValueObject \
        --dir src/Model/ValueObject \
        --root-ns Your.Project.Model.ValueObject \
        src/Model/NamespaceDescriptor/ValueObject*.php

Namespaces can be specified using any number of punctuation characters per component.

The following command line generates event classes for all descriptor interfaces found in the specified PHP files and puts them inside `src/Model/Event` with the namespace `\Your\Project\Model\Event`. Where the descriptors declare unresolved properties these are assumed to belong in the namespace `\Your\Project\Model\ValueObject`

    php vendor/bin/serge-codegen.php \
        --type EventPayload \
        --dir src/Model/Event \
        --root-ns Your\\Project:Model///Event \
        --param-ns Your.Project,Model@ValueObject \
        src/Model/NamespaceDescriptor/EventPayload*.php
