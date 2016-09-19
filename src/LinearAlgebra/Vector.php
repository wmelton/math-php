<?php
namespace Math\LinearAlgebra;

use Math\Functions\Map;

/**
 * 1 x n Vector
 */
class Vector implements \Countable, \ArrayAccess, \JsonSerializable
{
    /**
     * Number of elements
     * @var int
     */
    private $n;

    /**
     * Vector
     * @var array
     */
    private $A;

    /**
     * Constructor
     * @param array $A 1 x n vector
     */
    public function __construct(array $A)
    {
        $this->A = $A;
        $this->n = count($A);
    }

    /**************************************************************************
     * BASIC VECTOR GETTERS
     *  - getVector
     *  - getN
     *  - get
     *  - asColumnMatrix
     **************************************************************************/

    /**
     * Get matrix
     * @return array of arrays
     */
    public function getVector(): array
    {
        return $this->A;
    }

    /**
     * Get item count (n)
     * @return int number of items
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * Get a specific value at position i
     *
     * @param  int    $i index
     * @return number
     */
    public function get(int $i)
    {
        if ($i >= $this->n) {
            throw new \Exception("Element $i does not exist");
        }

        return $this->A[$i];
    }

    /**
     * Get the vector as an nx1 column matrix
     *
     * Example:
     *  V = [1, 2, 3]
     *
     *      [1]
     *  R = [2]
     *      [3]
     *
     * @return Matrix
     */
    public function asColumnMatrix()
    {
        $matrix = [];
        foreach ($this->A as $element) {
            $matrix[] = [$element];
        }

        return new Matrix($matrix);
    }

    /**************************************************************************
     * VECTOR OPERATIONS - Return a number
     *  - sum
     *  - length (magnitude)
     *  - dotProduct (innerProduct)
     *  - perpDotProduct
     **************************************************************************/

    /**
     * Sum of all elements
     *
     * @return number
     */
    public function sum()
    {
        return array_sum($this->A);
    }

    /**
     * Vector length (magnitude)
     * Same as l2-norm
     *
     * @return number
     */
    public function length()
    {
        return $this->l2norm();
    }

    /**
     * Dot product (inner product) (A⋅B)
     * https://en.wikipedia.org/wiki/Dot_product
     *
     * @param Vector $B
     *
     * @return number
     */
    public function dotProduct(Vector $B)
    {
        if ($B->getN() !== $this->n) {
            throw new \Exception('Vectors have different number of items');
        }

        return array_sum(array_map(
            function ($a, $b) {
                return $a * $b;
            },
            $this->A,
            $B->getVector()
        ));
    }

    /**
     * Inner product (convience method for dot product) (A⋅B)
     *
     * @param Vector $B
     *
     * @return number
     */
    public function innerProduct(Vector $B)
    {
        return $this->dotProduct($B);
    }

    /**
     * Perp dot product (A⊥⋅B)
     * A modification of the two-dimensional dot product in which A is
     * replaced by the perpendicular vector rotated 90º degrees.
     * http://mathworld.wolfram.com/PerpDotProduct.html
     *
     * @param Vector $B
     *
     * @return number
     */
    public function perpDotProduct(Vector $B)
    {
        if ($this->n !== 2 || $B->getN() !== 2) {
            throw new \Exception('Cannot do perp dot product unless both vectors are two-dimensional');
        }

        $A⊥ = $this->perpendicular();

        return $A⊥->dotProduct($B);
    }

    /**************************************************************************
     * VECTOR OPERATIONS - Return a Vector or Matrix
     *  - add
     *  - subtract
     *  - scalarMultiply
     *  - outerProduct
     *  - crossProduct
     *  - normalize
     *  - perpendicular
     **************************************************************************/

    /**
     * Add (A + B)
     *
     * A = [a₁, a₂, a₃]
     * B = [b₁, b₂, b₃]
     * A + B = [a₁ + b₁, a₂ + b₂, a₃ + b₃]
     *
     * @param Vector $B
     *
     * @return Vector
     */
    public function add(Vector $B): Vector
    {
        if ($B->getN() !== $this->n) {
            throw new \Exception('Vectors must be the same length for addition');
        }

        $R = Map\Multi::add($this->A, $B->getVector());
        return new Vector($R);
    }

    /**
     * Subtract (A - B)
     *
     * A = [a₁, a₂, a₃]
     * B = [b₁, b₂, b₃]
     * A - B = [a₁ - b₁, a₂ - b₂, a₃ - b₃]
     *
     * @param Vector $B
     *
     * @return Vector
     */
    public function subtract(Vector $B): Vector
    {
        if ($B->getN() !== $this->n) {
            throw new \Exception('Vectors must be the same length for subtraction');
        }

        $R = Map\Multi::subtract($this->A, $B->getVector());
        return new Vector($R);
    }

    /**
     * Scalar multiplication (scale)
     * kA = [k * a₁, k * a₂, k * a₃ ...]
     *
     * @param number $k Scale factor
     *
     * @return Vector
     */
    public function scalarMultiply($k)
    {
        return new Vector(Map\Single::multiply($this->A, $k));
    }

    /**
     * Scalar divide
     * kA = [k / a₁, k / a₂, k / a₃ ...]
     *
     * @param number $k Scale factor
     *
     * @return Vector
     */
    public function scalarDivide($k)
    {
        return new Vector(Map\Single::divide($this->A, $k));
    }

    /**
     * Outer product (A⨂B)
     * https://en.wikipedia.org/wiki/Outer_product
     *
     * @param Vector $B
     *
     * @return Matrix
     */
    public function outerProduct(Vector $B): Matrix
    {
        $m = $this->n;
        $n = $B->getN();
        $R = [];

        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $R[$i][$j] = $this->A[$i] * $B[$j];
            }
        }

        return MatrixFactory::create($R);
    }

    /**
     * Cross product (AxB)
     * https://en.wikipedia.org/wiki/Cross_product
     *
     *         | i  j  k  |
     * A x B = | a₀ a₁ a₂ | = |a₁ a₂|  - |a₀ a₂|  + |a₀ a₁|
     *         | b₀ b₁ b₂ |   |b₁ b₂|i   |b₀ b₂|j   |b₀ b₁|k
     *
     *       = (a₁b₂ - b₁a₂) - (a₀b₂ - b₀a₂) + (a₀b₁ - b₀a₁)
     *
     * @param Vector $B
     *
     * @return Vector
     */
    public function crossProduct(Vector $B)
    {
        if ($B->getN() !== 3 || $this->n !== 3) {
            throw new \Exception('Vectors must have 3 items');
        }

        $s1 =   ($this->A[1] * $B[2]) - ($this->A[2] * $B[1]);
        $s2 = -(($this->A[0] * $B[2]) - ($this->A[2] * $B[0]));
        $s3 =   ($this->A[0] * $B[1]) - ($this->A[1] * $B[0]);

        return new Vector([$s1, $s2, $s3]);
    }

    /**
     * Normalize (Â)
     * The normalized vector Â is a vector in the same direction of A
     * but with a norm (length) of 1. It is a unit vector.
     * http://mathworld.wolfram.com/NormalizedVector.html
     *
     *      A
     * Â ≡ ---
     *     |A|
     *
     *  where |A| is the l²-norm (|A|₂)
     *
     * @return Vector
     */
    public function normalize(): Vector
    {
        $│A│ = $this->l2norm();

        return $this->scalarDivide($│A│);
    }

    /**
     * Perpendicular (A⊥)
     * A vector perpendicular to A (A-perp) with the length that is rotated 90º
     * counter clockwise.
     *
     *     [a]       [-b]
     * A = [b]  A⊥ = [a]
     *
     * @return Vector
     */
    public function perpendicular(): Vector
    {
        if ($this->n !== 2) {
            throw new \Exception('Perpendicular operation only makes sense for 2D vector. 3D and higher vectors have infinite perpendular vectors.');
        }

        $A⊥ = [-$this->A[1], $this->A[0]];

        return new Vector($A⊥);
    }

    /**************************************************************************
     * VECTOR NORMS
     *  - l1Norm
     *  - l2Norm
     *  - pNorm
     *  - maxNorm
     **************************************************************************/

    /**
     * l₁-norm (|x|₁)
     * Also known as Taxicab norm or Manhattan norm
     *
     * https://en.wikipedia.org/wiki/Norm_(mathematics)#Taxicab_norm_or_Manhattan_norm
     *
     * |x|₁ = ∑|xᵢ|
     *
     * @return number
     */
    public function l1Norm()
    {
        return array_sum(Map\Single::abs($this->A));
    }

    /**
     * l²-norm (|x|₂)
     * Also known as Euclidean norm, Euclidean length, L² distance, ℓ² distance
     * Used to normalize a vector.
     *
     * http://mathworld.wolfram.com/L2-Norm.html
     * https://en.wikipedia.org/wiki/Norm_(mathematics)#Euclidean_norm
     *         ______
     * |x|₂ = √∑|xᵢ|²
     *
     * @return number
     */
    public function l2Norm()
    {
        return sqrt(array_sum(Map\Single::square($this->A)));
    }

    /**
     * p-norm (|x|p)
     * Also known as lp norm
     *
     * https://en.wikipedia.org/wiki/Norm_(mathematics)#p-norm
     *
     * |x|p = (∑|xᵢ|ᵖ)¹/ᵖ
     *
     * @return number
     */
    public function pNorm($p)
    {
        return array_sum(Map\Single::pow($this->A, $p))**(1/$p);
    }

    /**
     * Max norm (infinity norm) (|x|∞)
     *
     * |x|∞ = max |x|
     *
     * @return number
     */
    public function maxNorm()
    {
        return max(Map\Single::abs($this->A));
    }

    /**************************************************************************
     * PHP MAGIC METHODS
     *  - __toString
     **************************************************************************/

    /**
     * Print the vector as a string
     * Ex:
     *  [1, 2, 3]
     *
     * @return string
     */
    public function __toString()
    {
        return '[' . implode(', ', $this->A) . ']';
    }

    /**************************************************************************
     * Countable INTERFACE
     **************************************************************************/

    public function count(): int
    {
        return count($this->A);
    }

    /**************************************************************************
     * ArrayAccess INTERFACE
     **************************************************************************/

    public function offsetExists($i): bool
    {
        return isset($this->A[$i]);
    }

    public function offsetGet($i)
    {
        return $this->A[$i];
    }

    public function offsetSet($i, $value)
    {
        throw new \Exception('Vector class does not allow setting values');
    }

    public function offsetUnset($i)
    {
        throw new \Exception('Vector class does not allow unsetting values');
    }

    /**************************************************************************
     * JsonSerializable INTERFACE
     **************************************************************************/

    public function jsonSerialize()
    {
        return $this->A;
    }
}
