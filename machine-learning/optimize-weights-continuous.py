import json
import subprocess
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from tensorflow import keras
from tensorflow.keras import layers
import pickle
import time
from datetime import datetime
import os
import random

# Event IDs and their original weights
EVENTS = {
    'VPRWG': {'weight': 9, 'positive': 'higher', 'name': 'Nonfarm Payrolls'},
    'ZBEYU': {'weight': 9, 'positive': 'higher', 'name': 'Avg Hourly Earnings MoM'},
    'GKORG': {'weight': 9, 'positive': 'higher', 'name': 'Avg Hourly Earnings YoY'},
    'JCDYM': {'weight': 8, 'positive': 'lower', 'name': 'Unemployment Rate'},
    'LUXEM': {'weight': 6, 'positive': 'lower', 'name': 'Initial Jobless Claims'},
    'EEWPQ': {'weight': 5, 'positive': 'lower', 'name': 'Participation Rate'},
    'PIIRP': {'weight': 5, 'positive': 'lower', 'name': 'Continuing Jobless Claims'},
    'YHIYY': {'weight': 4, 'positive': 'higher', 'name': 'ADP Nonfarm Employment'},
    'MXSBY': {'weight': 4, 'positive': 'higher', 'name': 'JOLTS Job Openings'},
    'ASXLP': {'weight': 10, 'positive': 'higher', 'name': 'CPI MoM'},
    'YFVMV': {'weight': 10, 'positive': 'higher', 'name': 'Core CPI MoM'},
    'FCHGZ': {'weight': 9, 'positive': 'higher', 'name': 'PCE Deflator MoM'},
    'RQUMP': {'weight': 9, 'positive': 'higher', 'name': 'Core PCE Price Index MoM'},
    'ISFDE': {'weight': 7, 'positive': 'higher', 'name': 'CPI YoY'},
    'YPAXY': {'weight': 7, 'positive': 'higher', 'name': 'Core CPI YoY'},
    'LUNIH': {'weight': 7, 'positive': 'higher', 'name': 'PPI MoM'},
    'LIFLX': {'weight': 7, 'positive': 'higher', 'name': 'Core PPI MoM'},
    'HKJPS': {'weight': 6, 'positive': 'higher', 'name': 'PCE Deflator YoY'},
    'WDLPA': {'weight': 6, 'positive': 'higher', 'name': 'Core PCE Price Index YoY'},
    'ZORRY': {'weight': 10, 'positive': 'higher', 'name': 'FOMC Press Conference'},
    'HMUGW': {'weight': 9, 'positive': 'higher', 'name': 'FOMC Statement'},
    'ZVAPL': {'weight': 8, 'positive': 'higher', 'name': 'Fed Interest Rate Decision'},
    'ALMVK': {'weight': 8, 'positive': 'higher', 'name': 'Fed Chair Powell Speaks'},
    'BRHWF': {'weight': 8, 'positive': 'higher', 'name': 'Fed Chair Powell Testifies'},
    'BZLYI': {'weight': 8, 'positive': 'higher', 'name': 'FOMC Economic Projections'},
    'IEDWO': {'weight': 7, 'positive': 'higher', 'name': 'FOMC Meeting Minutes'},
    'XWBVZ': {'weight': 6, 'positive': 'higher', 'name': 'Fed Monetary Policy Report'},
    'ZJOIV': {'weight': 6, 'positive': 'higher', 'name': 'Feds Balance Sheet'},
    'FLPVV': {'weight': 5, 'positive': 'higher', 'name': 'Beige Book'},
}

# Results log file
RESULTS_FILE = 'weight_optimization_results.csv'
BEST_WEIGHTS_FILE = 'best_weights.json'

def update_weights_in_js(weights_dict):
    """Update EVENT_IMPACT_MAP in generate-training-data.js with new weights"""
    with open('generate-training-data.js', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Build the new EVENT_IMPACT_MAP
    event_map_lines = []
    for event_id, info in weights_dict.items():
        event_map_lines.append(
            f"    '{event_id}': {{ weight: {info['weight']:.2f}, positive: '{info['positive']}' }},"
        )
    
    # Find and replace the EVENT_IMPACT_MAP section
    start_marker = "const EVENT_IMPACT_MAP = {"
    end_marker = "};"
    
    start_idx = content.find(start_marker)
    if start_idx == -1:
        print("ERROR: Could not find EVENT_IMPACT_MAP in JS file")
        return False
    
    # Find the closing brace
    brace_count = 0
    i = start_idx + len(start_marker)
    while i < len(content):
        if content[i] == '{':
            brace_count += 1
        elif content[i] == '}':
            if brace_count == 0:
                end_idx = i + 1
                break
            brace_count -= 1
        i += 1
    
    new_map = start_marker + "\n" + "\n".join(event_map_lines) + "\n" + end_marker
    new_content = content[:start_idx] + new_map + content[end_idx:]
    
    with open('generate-training-data.js', 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    return True

def generate_training_data():
    """Run Node.js script to generate training data"""
    try:
        result = subprocess.run(
            ['node', 'generate-training-data.js'],
            capture_output=True,
            text=True,
            timeout=300  # 5 minutes max
        )
        return result.returncode == 0
    except Exception as e:
        print(f"Error generating training data: {e}")
        return False

def train_and_evaluate():
    """Train LSTM and return accuracy"""
    try:
        # Load data
        data = pd.read_csv('preprocessed_data/training-data.csv')
        X = data[['next_week_state_of_the_economy']].values
        y_direction = (data['direction'] == 'UP').astype(int).values
        
        # Split
        X_train, X_test, y_train, y_test = train_test_split(
            X, y_direction, test_size=0.2, random_state=42, shuffle=False
        )
        
        # Scale
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        # Reshape for LSTM
        X_train_lstm = X_train_scaled.reshape((X_train_scaled.shape[0], 1, 1))
        X_test_lstm = X_test_scaled.reshape((X_test_scaled.shape[0], 1, 1))
        
        # Build model
        model = keras.Sequential([
            layers.LSTM(64, activation='tanh', return_sequences=True, input_shape=(1, 1)),
            layers.Dropout(0.2),
            layers.LSTM(32, activation='tanh'),
            layers.Dropout(0.2),
            layers.Dense(16, activation='relu'),
            layers.Dense(1, activation='sigmoid')
        ])
        
        model.compile(
            optimizer=keras.optimizers.Adam(learning_rate=0.001),
            loss='binary_crossentropy',
            metrics=['accuracy']
        )
        
        # Train (reduced epochs for speed)
        model.fit(
            X_train_lstm, y_train,
            epochs=30,
            batch_size=32,
            validation_split=0.2,
            verbose=0
        )
        
        # Evaluate
        y_pred_proba = model.predict(X_test_lstm, verbose=0).flatten()
        y_pred = (y_pred_proba > 0.5).astype(int)
        accuracy = np.mean(y_pred == y_test)
        
        return accuracy, len(data)
    
    except Exception as e:
        print(f"Error training model: {e}")
        return 0.0, 0

def mutate_weights(weights_dict, mutation_rate=0.3, mutation_strength=0.5):
    """Create new weights by mutating existing ones"""
    new_weights = {}
    for event_id, info in weights_dict.items():
        new_info = info.copy()
        
        # Randomly mutate weight
        if random.random() < mutation_rate:
            current_weight = info['weight']
            # Add random value between -mutation_strength and +mutation_strength
            change = random.uniform(-mutation_strength, mutation_strength) * 10  # Scale to 0-10 range
            new_weight = current_weight + change
            # Clamp to 0-10
            new_weight = max(0.0, min(10.0, new_weight))
            new_info['weight'] = round(new_weight, 2)
        
        new_weights[event_id] = new_info
    
    return new_weights

def log_result(iteration, weights_dict, accuracy, sample_count):
    """Log results to CSV"""
    result = {
        'iteration': iteration,
        'timestamp': datetime.now().isoformat(),
        'accuracy': accuracy,
        'sample_count': sample_count
    }
    
    # Add all weights
    for event_id, info in weights_dict.items():
        result[f'weight_{event_id}'] = info['weight']
    
    df = pd.DataFrame([result])
    
    if not os.path.exists(RESULTS_FILE):
        df.to_csv(RESULTS_FILE, index=False)
    else:
        df.to_csv(RESULTS_FILE, mode='a', header=False, index=False)

def save_best_weights(weights_dict, accuracy):
    """Save best weights to JSON"""
    best_data = {
        'accuracy': accuracy,
        'timestamp': datetime.now().isoformat(),
        'weights': weights_dict
    }
    with open(BEST_WEIGHTS_FILE, 'w') as f:
        json.dump(best_data, f, indent=2)

def load_best_weights():
    """Load best weights from JSON"""
    if os.path.exists(BEST_WEIGHTS_FILE):
        with open(BEST_WEIGHTS_FILE, 'r') as f:
            data = json.load(f)
            return data['weights'], data['accuracy']
    return EVENTS.copy(), 0.0

def main():
    print("="*70)
    print("CONTINUOUS WEIGHT OPTIMIZATION")
    print("="*70)
    print(f"Optimizing {len(EVENTS)} event weights")
    print(f"Weight range: 0.00 - 10.00 (0.01 increments)")
    print(f"Results saved to: {RESULTS_FILE}")
    print(f"Best weights saved to: {BEST_WEIGHTS_FILE}")
    print("="*70)
    print("\nPress Ctrl+C to stop\n")
    
    # Load best known weights or start with defaults
    current_weights, best_accuracy = load_best_weights()
    
    iteration = 0
    
    try:
        while True:
            iteration += 1
            start_time = time.time()
            
            print(f"\n{'='*70}")
            print(f"ITERATION {iteration}")
            print(f"{'='*70}")
            print(f"Current best accuracy: {best_accuracy*100:.2f}%")
            
            # Generate new weights (mutate from current best)
            if iteration == 1:
                test_weights = current_weights
            else:
                test_weights = mutate_weights(current_weights)
            
            # Update JS file
            print("Updating weights in generate-training-data.js...")
            if not update_weights_in_js(test_weights):
                print("Failed to update weights, skipping...")
                continue
            
            # Generate training data
            print("Generating training data...")
            if not generate_training_data():
                print("Failed to generate training data, skipping...")
                continue
            
            # Train and evaluate
            print("Training LSTM model...")
            accuracy, sample_count = train_and_evaluate()
            
            elapsed = time.time() - start_time
            print(f"\nResults:")
            print(f"  Accuracy: {accuracy*100:.2f}%")
            print(f"  Samples: {sample_count}")
            print(f"  Time: {elapsed:.1f}s")
            
            # Log results
            log_result(iteration, test_weights, accuracy, sample_count)
            
            # Update best if improved
            if accuracy > best_accuracy:
                improvement = (accuracy - best_accuracy) * 100
                print(f"\n🎉 NEW BEST! Improved by {improvement:.2f} percentage points!")
                best_accuracy = accuracy
                current_weights = test_weights
                save_best_weights(test_weights, accuracy)
            else:
                print(f"  No improvement (best: {best_accuracy*100:.2f}%)")
            
            print(f"\nCompleted iteration {iteration} in {elapsed:.1f}s")
            
    except KeyboardInterrupt:
        print("\n\n" + "="*70)
        print("OPTIMIZATION STOPPED")
        print("="*70)
        print(f"Total iterations: {iteration}")
        print(f"Best accuracy: {best_accuracy*100:.2f}%")
        print(f"Best weights saved to: {BEST_WEIGHTS_FILE}")
        print(f"All results saved to: {RESULTS_FILE}")
        print("="*70)

if __name__ == '__main__':
    main()
