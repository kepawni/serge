# serge — Swiftly Establish Rich GraphQL Endpoints

Write your GraphQL schema and let serge generate your

- Command handlers
- Aggregates
- Event types
- Value objects

for CQRS and Event Sourcing.

## Quick start

Run `vendor/bin/serge-codegen` and notice a default configuration
popping into your working directory. Change the settings and run the
script again. This will give you a sample GraphQL schema to play with.
Use it to define your model and from now on (with both files in place)
calling `vendor/bin/serge-codegen` again will generate class files for
you that make up your domain model.

**Important!** Use Git or another code versioning, because these classes
will be overwritten every time the code generator is called.
Alternatively you may change the target directory in the config file and
carefully copy the classes into place on your own.

## GraphQL schema

To make this work, let's start off with the minimum requirements for our
GraphQL schema. We will focus on the command side here, so we keep the
query part to a minimum. In fact, it is recommended to have another
Web-facing endpoint for the query side altogether.

```
schema {
    query: CqrsQuery
    mutation: CqrsAggregateMutators
}
type CqrsQuery {
    status: Boolean!
}
```

The *query* part must be present in a valid GraphQL schema, so we define
*CqrsQuery* (you can name it however you want, by the way) as a regular
*type*. To meet the requirement for types to have at least one field, we
define *status*, so we have a simple means of testing if our endpoint is
working at all. This can catch quite a few problems like missing
dependencies, wrong config paths or just a server instance that didn't
come up as planned.

Now for the *mutation* part, which is covered by another *type* we named
*CqrsAggregateMutators* (again, you may change that name if you like).
When we define this *type*, we add a field for every aggregate in our
model:

### Declare aggregates

```
type CqrsAggregateMutators {
    Customer(id: ID!): Customer!
    Invoice(id: ID!): Invoice!
}
```

These fields need a parameter for passing the aggregate ID, so we use
`id: ID!` as a hard-coded convention. The return types of these fields
must match their names and are non-nullable, which is also a fixed
convention. This way, we now have declared the aggregates available in
our model and can now turn to the commands they are supposed to handle.

### Define commands per aggregate

So, we define another `type` for each of the aggregates and add our
commands as fields.

```
type Invoice {
    chargeCustomer(customerId: ID!, invoiceNumber: String, invoiceDate: Date): ID!
    appendLineItem(item: LineItem!): Boolean!
    correctMistypedInvoiceDate(invoiceDate: Date!): Boolean!
    overrideDueDate(dueDate: Date!): Boolean!
    removeLineItemByPosition(position: Int!): Boolean!
}
```

The return type follows another simple convention:
- `ID!` for factory commands that bring a new aggregate into existence
- `Boolean!` for regular commands that mutate an existing aggregate

The field names should be lowercase, because these will be the public
methods this aggregate provides.

### Define value objects

You may notice, that these commands use both built-in types (e. g.
`String`) and complex, yet undefined ones like `LineItem`. These are our
value objects and this is how we define them as *input* types:

```
input LineItem {
    quantity: Float!
    price: Money!
    title: String
}
input Money {
    amount: Float!
    currency: String!
}
```

As you can see, they even may refer to each other forming a complex and
flexible type system.

With these steps, we already have defined anything the code generator
needs to know for building our model classes, but when value objects can
be defined so easily, why not use this mechanism for the domain events
as well?

### Define event types

```
interface InvoiceEvents {
    CustomerWasCharged(customerId: ID!, invoiceNumber: String, invoiceDate: Date): Boolean!
    LineItemWasAppended(item: LineItem!): Boolean!
    MistypedInvoiceDateWasCorrected(invoiceDate: Date!): Boolean!
    DueDateWasOverridden(dueDate: Date!): Boolean!
    LineItemWasRemoved(position: Int!): Boolean!
}
```

To distinguish between value objects and events, we use *interface*
types here and another convention requires that we group events together
based on the aggregates that emits them, which is indicated in the name
(aggregate name + `Events`). This time, the field names start with an
uppercase letter, because these will be turned into event classes and
the parameters will be the event's properties. The `Boolean!` return
type is just another convention here.

## XML configuration

The GraphQL schema above defines the inner structure of our model, but
in order to generate actual class files we need a bit more context on
paths and namespaces and so on. That gap will be bridged by the file
`serge.config.xml` (the name is fixed).

If you call the code generator the first time, when there is no such
configuration file, it will generate one for you. See the quick start
for how to get going from scratch.
