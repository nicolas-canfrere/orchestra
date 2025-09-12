# Détection et Prévention des Boucles Infinies dans Orchestra State Machine

## Problème Identifié

Le moteur d'état `Engine::executeTransition()` peut créer des **boucles infinies** si le workflow contient des **références circulaires** entre les états.

### Code Actuel Vulnérable

```php
public function executeTransition(StateInterface $currentState): void
{
    $nextTransition = $currentState->getNextTransition();

    while (null !== $nextTransition) {
        $this->executeAction($nextTransition->getAction());
        $nextTransition = $nextTransition->getToState()->getNextTransition();
    }
}
```

### Scénario Problématique

```
État A → Transition vers État B
État B → Transition vers État A
```

Le `while` ne s'arrêtera jamais car il y aura toujours une `nextTransition` disponible.

## Solutions Proposées

### 🔄 Solution 1 : Compteur de Transitions (Simple)

**Principe :** Limiter le nombre maximum de transitions autorisées.

```php
final class Engine implements EngineInterface
{
    private const MAX_TRANSITIONS = 1000;

    public function executeTransition(StateInterface $currentState): void
    {
        $transitionCount = 0;
        $nextTransition = $currentState->getNextTransition();

        while (null !== $nextTransition) {
            if (++$transitionCount > self::MAX_TRANSITIONS) {
                throw new InfiniteLoopException(
                    sprintf('Maximum transitions exceeded (%d)', self::MAX_TRANSITIONS)
                );
            }

            $this->executeAction($nextTransition->getAction());
            $nextTransition = $nextTransition->getToState()->getNextTransition();
        }
    }
}
```

**✅ Avantages :**
- Simple à implémenter
- Impact performance minimal
- Protection immédiate

**❌ Inconvénients :**
- Limite arbitraire difficile à définir
- Ne détecte pas vraiment les cycles
- Peut bloquer des workflows légitimes longs

---

### 🎯 Solution 2 : Suivi des États Visités (Recommandée)

**Principe :** Détecter les cycles en suivant les états déjà traversés.

```php
final class Engine implements EngineInterface
{
    private const MAX_TRANSITIONS = 1000;

    public function executeTransition(StateInterface $currentState): void
    {
        $visitedStates = new \SplObjectStorage();
        $transitionCount = 0;
        $nextTransition = $currentState->getNextTransition();

        while (null !== $nextTransition) {
            // Protection contre les boucles infinies par compteur
            if (++$transitionCount > self::MAX_TRANSITIONS) {
                throw new InfiniteLoopException(
                    sprintf('Maximum transitions exceeded (%d)', self::MAX_TRANSITIONS)
                );
            }

            $toState = $nextTransition->getToState();
            
            // Détection précise des cycles
            if ($visitedStates->contains($toState)) {
                $stateNames = [];
                foreach ($visitedStates as $state) {
                    $stateNames[] = $state->getName();
                }
                $stateNames[] = $toState->getName();
                
                throw new CircularTransitionException(
                    sprintf('Circular transition detected: %s', implode(' → ', $stateNames))
                );
            }

            $visitedStates->attach($toState);
            $this->executeAction($nextTransition->getAction());
            $nextTransition = $toState->getNextTransition();
        }
    }
}
```

**✅ Avantages :**
- Détection précise des cycles
- Messages d'erreur informatifs avec le chemin
- Combine avec compteur pour double sécurité

**❌ Inconvénients :**
- Consommation mémoire O(n) états
- Ne permet pas les boucles légitimes (patterns retry)

---

### ⏱️ Solution 3 : Timeout d'Exécution

**Principe :** Limiter le temps d'exécution total du workflow.

```php
final class Engine implements EngineInterface
{
    private const EXECUTION_TIMEOUT_SECONDS = 30;

    public function executeTransition(StateInterface $currentState): void
    {
        $startTime = time();
        $nextTransition = $currentState->getNextTransition();

        while (null !== $nextTransition) {
            if (time() - $startTime > self::EXECUTION_TIMEOUT_SECONDS) {
                throw new ExecutionTimeoutException(
                    sprintf('Workflow execution timeout (%d seconds)', self::EXECUTION_TIMEOUT_SECONDS)
                );
            }

            $this->executeAction($nextTransition->getAction());
            $nextTransition = $nextTransition->getToState()->getNextTransition();
        }
    }
}
```

**✅ Avantages :**
- Protection absolue contre les blocages
- Utile pour les environnements de production

**❌ Inconvénients :**
- Délai avant détection
- Peut interrompre des workflows légitimes longs
- Pas de diagnostic précis de la cause

---

### 🔍 Solution 4 : Validation du Graphe (Avancée)

**Principe :** Analyser le workflow avant exécution pour détecter les cycles.

```php
final class WorkflowValidator
{
    public function validateNoCycles(ProcessDefinitionInterface $processDefinition): void
    {
        $visited = new \SplObjectStorage();
        $recursionStack = new \SplObjectStorage();
        
        $this->dfsCheck($processDefinition->getStartState(), $visited, $recursionStack);
    }

    private function dfsCheck(
        StateInterface $state, 
        \SplObjectStorage $visited, 
        \SplObjectStorage $recursionStack
    ): void {
        $visited->attach($state);
        $recursionStack->attach($state);

        $transition = $state->getNextTransition();
        if (null !== $transition) {
            $nextState = $transition->getToState();
            
            if (!$visited->contains($nextState)) {
                $this->dfsCheck($nextState, $visited, $recursionStack);
            } elseif ($recursionStack->contains($nextState)) {
                throw new CircularWorkflowException(
                    sprintf('Cycle detected involving state: %s', $nextState->getName())
                );
            }
        }

        $recursionStack->detach($state);
    }
}

// Usage dans l'Engine
final class Engine implements EngineInterface
{
    private WorkflowValidator $validator;

    public function __construct(WorkflowValidator $validator = null)
    {
        $this->validator = $validator ?? new WorkflowValidator();
    }

    public function launch(ProcessDefinitionInterface $processDefinition): void
    {
        // Validation préventive
        $this->validator->validateNoCycles($processDefinition);
        
        $this->executeTransition($processDefinition->getStartState());
    }
}
```

**✅ Avantages :**
- Détection préventive (avant exécution)
- Aucun impact sur les performances d'exécution
- Diagnostic précis

**❌ Inconvénients :**
- Complexité d'implémentation élevée
- Difficile avec des workflows dynamiques
- Analyse statique uniquement

---

## Exceptions Personnalisées

```php
<?php

declare(strict_types=1);

namespace App\StateMachine\Exception;

final class InfiniteLoopException extends \RuntimeException
{
    public function __construct(string $message = 'Infinite loop detected in state machine execution')
    {
        parent::__construct($message);
    }
}

final class CircularTransitionException extends \RuntimeException
{
    public function __construct(string $message = 'Circular transition detected in workflow')
    {
        parent::__construct($message);
    }
}

final class ExecutionTimeoutException extends \RuntimeException
{
    public function __construct(string $message = 'Workflow execution timeout')
    {
        parent::__construct($message);
    }
}

final class CircularWorkflowException extends \RuntimeException
{
    public function __construct(string $message = 'Circular workflow detected during validation')
    {
        parent::__construct($message);
    }
}
```

## Recommandations d'Implémentation

### 🥇 Approche Recommandée : Solution Hybride

**Phase 1 : Implémentation immédiate**
- Utiliser **Solution 2** (Suivi des États Visités)
- Ajouter les exceptions personnalisées
- Mettre à jour les tests unitaires

**Phase 2 : Amélioration future**
- Implémenter **Solution 4** (Validation du Graphe)
- Permettre la configuration des limites
- Ajouter des métriques de performance

### Configuration Recommandée

```php
final class Engine implements EngineInterface
{
    private const DEFAULT_MAX_TRANSITIONS = 1000;
    private const DEFAULT_EXECUTION_TIMEOUT = 30;

    private int $maxTransitions;
    private int $executionTimeout;

    public function __construct(
        int $maxTransitions = self::DEFAULT_MAX_TRANSITIONS,
        int $executionTimeout = self::DEFAULT_EXECUTION_TIMEOUT
    ) {
        $this->maxTransitions = $maxTransitions;
        $this->executionTimeout = $executionTimeout;
    }
}
```

## Tests Unitaires Suggérés

```php
public function testDetectsCircularTransition(): void
{
    $state1 = new State('state1');
    $state2 = new State('state2');
    
    // Créer une boucle : state1 → state2 → state1
    $transition1 = $state1->then($state2);
    $transition2 = $state2->then($state1);

    $this->expectException(CircularTransitionException::class);
    $this->expectExceptionMessage('Circular transition detected: state2 → state1');
    
    $this->engine->executeTransition($state1);
}

public function testMaxTransitionsExceeded(): void
{
    $engine = new Engine(maxTransitions: 5);
    
    // Créer une chaîne longue de 10 états
    $states = $this->createLinearStateChain(10);
    
    $this->expectException(InfiniteLoopException::class);
    $this->expectExceptionMessage('Maximum transitions exceeded (5)');
    
    $engine->executeTransition($states[0]);
}
```

## Considérations de Performance

| Solution | Complexité Temps | Complexité Espace | Impact Runtime |
|----------|------------------|-------------------|----------------|
| Compteur | O(1) | O(1) | Minimal |
| États Visités | O(n) | O(n) | Faible |
| Timeout | O(1) | O(1) | Minimal |
| Validation Graphe | O(V+E) | O(V) | Aucun (pré-execution) |

**Recommandation :** Commencer par la **Solution 2** puis évoluer vers la **Solution 4** pour les workflows complexes.

## Conclusion

La détection des boucles infinies est **critique** pour la robustesse du state machine Orchestra. L'approche hybride combinant suivi d'états et validation de graphe offre le meilleur équilibre entre **sécurité**, **performance** et **diagnostics**.

L'implémentation devrait être **progressive** : protection immédiate avec la Solution 2, puis évolution vers une validation préventive complète.