# Fonctionnalité

créer et configurer un "registry" dans lequel on pourra enregistrer des Actions (ActionInterface)

On ajoutera ces actions au registry à l'aide d'un compiler pass de Symfony (DependancyInjection)

On pourras injecter ce "registry"  dans les classes implementant ProcessDefinitionInterface.

# Plan d'implémentation de l'ActionRegistry

## Architecture analysée
- **ActionInterface** : Interface simple avec méthode `run(array $parameters): void`
- **ProcessDefinition** : Utilise actuellement `withAction()` sur les transitions pour attacher des actions
- **Injection** : Système Symfony avec autowiring activé, pas de compiler pass existant

## Implémentation proposée

### 1. Interface ActionRegistryInterface
```php
interface ActionRegistryInterface {
    public function register(string $name, ActionInterface $action): void;
    public function get(string $name): ?ActionInterface;
    public function has(string $name): bool;
    public function getAll(): array;
}
```

### 2. Classe ActionRegistry
- Implémentation concrete avec stockage en array
- Les noms des actions seront les Full Qualified Name
- Gestion des erreurs pour actions inexistantes

### 3. Compiler Pass Symfony
- `ActionRegistryCompilerPass` qui scanne les services taggués
- Auto-registration des actions via tag `workflow.action`

### 4. Configuration Symfony
- Service ActionRegistry dans `services.yaml`
- Configuration du compiler pass
- Tags automatiques pour les services implémentant ActionInterface

### 5. Adaptation ProcessDefinition
- Modification de `AbstractProcessDefinition` pour injecter le registry
- Méthodes helper pour récupérer les actions par nom

### 6. Tests
- Tests unitaires pour ActionRegistry et ActionRegistryInterface
- Tests d'intégration avec ProcessDefinition
- Tests du compiler pass avec services mockés

## Structure des fichiers
```
src/StateMachine/Action/
├── ActionRegistryInterface.php
└── ActionRegistry.

src/DependencyInjection/Pass
└── ActionRegistryCompilerPass.php


tests/Unit/StateMachine/Action/
├── ActionRegistryTest.php
└── ActionRegistryCompilerPassTest.php
```

Cette implémentation respecte l'architecture existante et permet une injection propre dans les ProcessDefinition tout en gardant la rétrocompatibilité.
