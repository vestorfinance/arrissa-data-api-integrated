import json
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
import matplotlib.pyplot as plt

# Event impact configuration
EVENT_CONFIG = {
    'VPRWG': {'positive': 'higher'},
    'ZBEYU': {'positive': 'higher'},
    'GKORG': {'positive': 'higher'},
    'JCDYM': {'positive': 'lower'},
    'LUXEM': {'positive': 'lower'},
    'EEWPQ': {'positive': 'lower'},
    'PIIRP': {'positive': 'lower'},
    'YHIYY': {'positive': 'higher'},
    'MXSBY': {'positive': 'higher'},
    'ASXLP': {'positive': 'higher'},
    'YFVMV': {'positive': 'higher'},
    'FCHGZ': {'positive': 'higher'},
    'RQUMP': {'positive': 'higher'},
    'ISFDE': {'positive': 'higher'},
    'YPAXY': {'positive': 'higher'},
    'LUNIH': {'positive': 'higher'},
    'LIFLX': {'positive': 'higher'},
    'HKJPS': {'positive': 'higher'},
    'WDLPA': {'positive': 'higher'},
    'ZORRY': {'positive': 'higher'},
    'HMUGW': {'positive': 'higher'},
    'ZVAPL': {'positive': 'higher'},
    'ALMVK': {'positive': 'higher'},
    'BRHWF': {'positive': 'higher'},
    'BZLYI': {'positive': 'higher'},
    'IEDWO': {'positive': 'higher'},
    'XWBVZ': {'positive': 'higher'},
    'ZJOIV': {'positive': 'higher'},
    'FLPVV': {'positive': 'higher'},
}

# Default weights (all 5)
DEFAULT_WEIGHTS = {event_id: 5.0 for event_id in EVENT_CONFIG.keys()}

def calculate_next_week_economy_state(events_data, weights):
    """Calculate economy state using provided weights"""
    if not events_data or 'vestor_data' not in events_data:
        return 0.0
    
    aggregate_weight = 0
    event_count = 0
    
    for category in events_data['vestor_data'].values():
        if 'events' not in category or not isinstance(category['events'], list):
            continue
        
        for event in category['events']:
            event_id = event.get('consistent_event_id')
            if event_id not in EVENT_CONFIG:
                continue
            
            forecast = event.get('forecast_value')
            previous = event.get('previous_value')
            
            if forecast is None or previous is None:
                continue
            
            try:
                forecast_val = float(forecast)
                previous_val = float(previous)
            except:
                continue
            
            if forecast_val == previous_val:
                continue
            
            weight = weights.get(event_id, 5.0)
            config = EVENT_CONFIG[event_id]
            
            signed_weight = 0
            if config['positive'] == 'higher':
                if forecast_val > previous_val:
                    signed_weight = weight
                elif forecast_val < previous_val:
                    signed_weight = -weight
            elif config['positive'] == 'lower':
                if forecast_val < previous_val:
                    signed_weight = weight
                elif forecast_val > previous_val:
                    signed_weight = -weight
            
            aggregate_weight += signed_weight
            event_count += 1
    
    if event_count > 0:
        should_be_weight = event_count * 10
        state_percentage = (aggregate_weight / should_be_weight) * 100
        return round(state_percentage, 2)
    
    return 0.0

def load_and_process_data(events_file, weights):
    """Load events and generate features"""
    with open(events_file, 'r') as f:
        all_data = json.load(f)
    
    rows = []
    for item in all_data:
        next_week_state = calculate_next_week_economy_state(item['events'], weights)
        rows.append({
            'date': item['date'],
            'next_week_state': next_week_state,
            'direction': item['direction'],
            'magnitude': item['magnitude']
        })
    
    return pd.DataFrame(rows)

class PrototypeClassifier:
    def __init__(self):
        self.up_prototype = None
        self.down_prototype = None
        self.up_std = None
        self.down_std = None
        self.up_samples = []
        self.down_samples = []
        
    def fit(self, X, y):
        """Learn prototypes for UP and DOWN separately"""
        # Split by class
        up_mask = y == 1
        down_mask = y == 0
        
        X_up = X[up_mask]
        X_down = X[down_mask]
        
        # Store all samples for visualization
        self.up_samples = X_up.flatten()
        self.down_samples = X_down.flatten()
        
        # Calculate prototypes (mean of each class)
        self.up_prototype = np.mean(X_up)
        self.down_prototype = np.mean(X_down)
        
        # Calculate standard deviations
        self.up_std = np.std(X_up)
        self.down_std = np.std(X_down)
        
        print("="*70)
        print("PROTOTYPE LEARNING")
        print("="*70)
        print(f"UP samples: {len(X_up)}")
        print(f"  Prototype (mean): {self.up_prototype:.2f}")
        print(f"  Std deviation: {self.up_std:.2f}")
        print(f"  Range: [{np.min(X_up):.2f}, {np.max(X_up):.2f}]")
        print()
        print(f"DOWN samples: {len(X_down)}")
        print(f"  Prototype (mean): {self.down_prototype:.2f}")
        print(f"  Std deviation: {self.down_std:.2f}")
        print(f"  Range: [{np.min(X_down):.2f}, {np.max(X_down):.2f}]")
        print("="*70)
        
    def predict(self, X):
        """Predict by distance to prototypes"""
        predictions = []
        
        for sample in X:
            # Calculate distance to each prototype
            dist_to_up = abs(sample - self.up_prototype)
            dist_to_down = abs(sample - self.down_prototype)
            
            # Predict closest prototype
            if dist_to_up < dist_to_down:
                predictions.append(1)  # UP
            else:
                predictions.append(0)  # DOWN
        
        return np.array(predictions)
    
    def predict_proba(self, X):
        """Return probability based on weighted distances"""
        probabilities = []
        
        for sample in X:
            dist_to_up = abs(sample - self.up_prototype)
            dist_to_down = abs(sample - self.down_prototype)
            
            # Convert distances to probabilities (closer = higher probability)
            total_dist = dist_to_up + dist_to_down
            if total_dist > 0:
                prob_up = dist_to_down / total_dist  # Inverse relationship
            else:
                prob_up = 0.5
            
            probabilities.append(prob_up)
        
        return np.array(probabilities)
    
    def visualize(self, save_path='prototype_visualization.png'):
        """Visualize the prototypes and distributions"""
        fig, axes = plt.subplots(2, 1, figsize=(12, 8))
        
        # Plot 1: Histograms
        axes[0].hist(self.up_samples, bins=30, alpha=0.6, color='green', label='UP', density=True)
        axes[0].hist(self.down_samples, bins=30, alpha=0.6, color='red', label='DOWN', density=True)
        axes[0].axvline(self.up_prototype, color='darkgreen', linestyle='--', linewidth=2, label=f'UP Prototype: {self.up_prototype:.2f}')
        axes[0].axvline(self.down_prototype, color='darkred', linestyle='--', linewidth=2, label=f'DOWN Prototype: {self.down_prototype:.2f}')
        axes[0].set_xlabel('Next Week Economy State (%)')
        axes[0].set_ylabel('Density')
        axes[0].set_title('Distribution of Economy States by Direction')
        axes[0].legend()
        axes[0].grid(True, alpha=0.3)
        
        # Plot 2: Decision boundary
        x_range = np.linspace(
            min(np.min(self.up_samples), np.min(self.down_samples)) - 10,
            max(np.max(self.up_samples), np.max(self.down_samples)) + 10,
            1000
        )
        
        # Calculate decision scores
        decision_scores = []
        for x in x_range:
            dist_up = abs(x - self.up_prototype)
            dist_down = abs(x - self.down_prototype)
            score = dist_down - dist_up  # Positive = UP, Negative = DOWN
            decision_scores.append(score)
        
        axes[1].plot(x_range, decision_scores, color='blue', linewidth=2)
        axes[1].axhline(0, color='black', linestyle='-', linewidth=1)
        axes[1].axvline(self.up_prototype, color='darkgreen', linestyle='--', linewidth=2, alpha=0.7)
        axes[1].axvline(self.down_prototype, color='darkred', linestyle='--', linewidth=2, alpha=0.7)
        axes[1].fill_between(x_range, 0, decision_scores, where=np.array(decision_scores) > 0, 
                             color='green', alpha=0.2, label='Predicted UP')
        axes[1].fill_between(x_range, decision_scores, 0, where=np.array(decision_scores) < 0,
                             color='red', alpha=0.2, label='Predicted DOWN')
        axes[1].set_xlabel('Next Week Economy State (%)')
        axes[1].set_ylabel('Decision Score (+ = UP, - = DOWN)')
        axes[1].set_title('Prototype-Based Decision Boundary')
        axes[1].legend()
        axes[1].grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig(save_path, dpi=150, bbox_inches='tight')
        print(f"\nVisualization saved: {save_path}")
        plt.close()

def main():
    print("="*70)
    print("PROTOTYPE-BASED CLASSIFIER")
    print("="*70)
    
    # Load training data
    print("\nLoading training data (2014-2024)...")
    df_train = load_and_process_data('events_data.json', DEFAULT_WEIGHTS)
    print(f"Training samples: {len(df_train)}")
    print(f"  UP: {sum(df_train['direction'] == 'UP')} ({sum(df_train['direction'] == 'UP')/len(df_train)*100:.1f}%)")
    print(f"  DOWN: {sum(df_train['direction'] == 'DOWN')} ({sum(df_train['direction'] == 'DOWN')/len(df_train)*100:.1f}%)")
    
    # Load test data
    print("\nLoading test data (2025-2026)...")
    df_test = load_and_process_data('test_events_data.json', DEFAULT_WEIGHTS)
    print(f"Test samples: {len(df_test)}")
    print(f"  UP: {sum(df_test['direction'] == 'UP')} ({sum(df_test['direction'] == 'UP')/len(df_test)*100:.1f}%)")
    print(f"  DOWN: {sum(df_test['direction'] == 'DOWN')} ({sum(df_test['direction'] == 'DOWN')/len(df_test)*100:.1f}%)")
    
    # Prepare data
    X_train = df_train[['next_week_state']].values
    y_train = (df_train['direction'] == 'UP').astype(int).values
    
    X_test = df_test[['next_week_state']].values
    y_test = (df_test['direction'] == 'UP').astype(int).values
    
    # Train prototype classifier
    print("\n")
    model = PrototypeClassifier()
    model.fit(X_train, y_train)
    
    # Visualize
    model.visualize('prototype_visualization.png')
    
    # Evaluate on training data
    print("\n" + "="*70)
    print("TRAINING PERFORMANCE")
    print("="*70)
    y_train_pred = model.predict(X_train)
    train_accuracy = np.mean(y_train_pred == y_train)
    print(f"Accuracy: {train_accuracy*100:.2f}%")
    
    # Confusion matrix
    train_up_correct = np.sum((y_train == 1) & (y_train_pred == 1))
    train_up_total = np.sum(y_train == 1)
    train_down_correct = np.sum((y_train == 0) & (y_train_pred == 0))
    train_down_total = np.sum(y_train == 0)
    
    print(f"\nUP predictions: {train_up_correct}/{train_up_total} ({train_up_correct/train_up_total*100:.1f}%)")
    print(f"DOWN predictions: {train_down_correct}/{train_down_total} ({train_down_correct/train_down_total*100:.1f}%)")
    
    # Evaluate on test data
    print("\n" + "="*70)
    print("TEST PERFORMANCE (2025-2026)")
    print("="*70)
    y_test_pred = model.predict(X_test)
    test_accuracy = np.mean(y_test_pred == y_test)
    print(f"Accuracy: {test_accuracy*100:.2f}%")
    
    # Confusion matrix
    test_up_correct = np.sum((y_test == 1) & (y_test_pred == 1))
    test_up_total = np.sum(y_test == 1)
    test_down_correct = np.sum((y_test == 0) & (y_test_pred == 0))
    test_down_total = np.sum(y_test == 0)
    
    print(f"\nUP predictions: {test_up_correct}/{test_up_total} ({test_up_correct/test_up_total*100:.1f}%)")
    print(f"DOWN predictions: {test_down_correct}/{test_down_total} ({test_down_correct/test_down_total*100:.1f}%)")
    
    # Compare to baseline
    print("\n" + "="*70)
    print("COMPARISON")
    print("="*70)
    print(f"Prototype Classifier (Train): {train_accuracy*100:.2f}%")
    print(f"Prototype Classifier (Test):  {test_accuracy*100:.2f}%")
    print(f"LSTM Baseline (was):          53.04%")
    print("="*70)
    
    # Save results
    results = {
        'method': 'Prototype Classifier',
        'train_accuracy': train_accuracy,
        'test_accuracy': test_accuracy,
        'up_prototype': model.up_prototype,
        'down_prototype': model.down_prototype,
        'decision_boundary': (model.up_prototype + model.down_prototype) / 2
    }
    
    with open('prototype_results.json', 'w') as f:
        json.dump(results, f, indent=2)
    
    print(f"\nResults saved: prototype_results.json")

if __name__ == '__main__':
    main()
